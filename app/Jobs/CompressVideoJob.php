<?php

namespace App\Jobs;

use App\Models\EducationMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;

class CompressVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 3;

    protected $mediaId;
    protected $originalPath;

    public function __construct(int $mediaId, string $originalPath)
    {
        $this->mediaId = $mediaId;
        $this->originalPath = $originalPath;
    }

    public function handle(): void
    {
        $media = EducationMedia::find($this->mediaId);
        
        if (!$media) {
            Log::error("Media not found: {$this->mediaId}");
            return;
        }

        try {
            $media->update(['processing_status' => 'processing', 'processing_progress' => 0]);

            $fullPath = Storage::disk('public')->path($this->originalPath);
            
            if (!file_exists($fullPath)) {
                throw new \Exception("File not found: {$fullPath}");
            }

            $tempPath = Storage::disk('public')->path('temp/' . basename($this->originalPath));
            $tempDir = dirname($tempPath);
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Detect environment
            $isProduction = config('app.env') === 'production';
            
            $ffmpegBinary = $isProduction 
                ? config('services.ffmpeg.binaries_prod', env('FFMPEG_BINARIES_PROD', '/usr/bin/ffmpeg'))
                : config('services.ffmpeg.binaries_dev', env('FFMPEG_BINARIES_DEV', 'C:/ffmpeg/bin/ffmpeg.exe'));
            
            $ffprobeBinary = $isProduction
                ? config('services.ffmpeg.probe_prod', env('FFPROBE_BINARIES_PROD', '/usr/bin/ffprobe'))
                : config('services.ffmpeg.probe_dev', env('FFPROBE_BINARIES_DEV', 'C:/ffmpeg/bin/ffprobe.exe'));

            Log::info("Using FFmpeg", [
                'environment' => config('app.env'),
                'ffmpeg' => $ffmpegBinary,
                'ffprobe' => $ffprobeBinary,
                'file' => $fullPath
            ]);

            if (!file_exists($ffmpegBinary)) {
                throw new \Exception("FFmpeg binary not found at: {$ffmpegBinary}");
            }
            if (!file_exists($ffprobeBinary)) {
                throw new \Exception("FFprobe binary not found at: {$ffprobeBinary}");
            }

            // Setup FFMpeg
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries'  => $ffmpegBinary,
                'ffprobe.binaries' => $ffprobeBinary,
                'timeout'          => 3600,
                'ffmpeg.threads'   => 2,
            ]);

            $video = $ffmpeg->open($fullPath);
            
            // Update progress: 10% - Generate thumbnail
            $media->update(['processing_progress' => 10]);
            $thumbnailPath = $this->generateThumbnail($video, $fullPath);
            
            if ($thumbnailPath) {
                $media->update(['thumbnail_path' => $thumbnailPath]);
                Log::info("Thumbnail generated: {$thumbnailPath}");
            }

            // Update progress: 20%
            $media->update(['processing_progress' => 20]);

            $originalSize = filesize($fullPath);
            Log::info("Original video size: " . $this->formatBytes($originalSize));

            // Setup compression format
            $format = new X264('aac', 'libx264');
            $format->setKiloBitrate(1000)
                   ->setAudioKiloBitrate(128)
                   ->setAdditionalParameters([
                       '-preset', 'medium',
                       '-crf', '23',
                       '-movflags', '+faststart',
                       '-pix_fmt', 'yuv420p'
                   ]);

            // Update progress: 40%
            $media->update(['processing_progress' => 40]);

            // Get video dimensions and resize if needed
            $videoStream = $ffmpeg->getFFProbe()
                ->streams($fullPath)
                ->videos()
                ->first();
                
            if ($videoStream) {
                $width = $videoStream->get('width');
                $height = $videoStream->get('height');
                
                Log::info("Original video dimensions: {$width}x{$height}");
                
                if ($height > 1080) {
                    $newHeight = 1080;
                    $newWidth = intval(($width / $height) * $newHeight);
                    
                    if ($newWidth % 2 !== 0) {
                        $newWidth++;
                    }
                    
                    Log::info("Resizing to: {$newWidth}x{$newHeight}");
                    $video->filters()->resample(new Dimension($newWidth, $newHeight));
                }
            }

            // Update progress: 60%
            $media->update(['processing_progress' => 60]);

            Log::info("Starting compression...");

            // Compress video with progress callback
            $format->on('progress', function ($video, $format, $percentage) use ($media) {
                // Map compression progress from 60% to 90%
                $progress = 60 + ($percentage * 0.3);
                $media->update(['processing_progress' => min(90, $progress)]);
            });

            $video->save($format, $tempPath);

            // Update progress: 95%
            $media->update(['processing_progress' => 95]);

            if (!file_exists($tempPath)) {
                throw new \Exception("Compressed file not created");
            }

            $compressedSize = filesize($tempPath);
            $reduction = round((1 - $compressedSize / $originalSize) * 100, 2);
            
            Log::info("Video compression completed", [
                'original_size' => $this->formatBytes($originalSize),
                'compressed_size' => $this->formatBytes($compressedSize),
                'reduction' => $reduction . '%'
            ]);

            // Replace original with compressed
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            
            if (!rename($tempPath, $fullPath)) {
                throw new \Exception("Failed to replace original file");
            }

            // Update progress: 100%
            $media->update([
                'processing_status' => 'completed',
                'processing_progress' => 100,
                'processing_error' => null
            ]);

            Log::info("Video processing completed successfully", [
                'media_id' => $this->mediaId,
                'thumbnail' => $thumbnailPath
            ]);

        } catch (\Exception $e) {
            Log::error("Video compression failed", [
                'media_id' => $this->mediaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $media->update([
                'processing_status' => 'failed',
                'processing_error' => $e->getMessage()
            ]);

            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }

            throw $e;
        }
    }

    private function generateThumbnail($video, string $fullPath): ?string
    {
        try {
            $fileName = pathinfo($fullPath, PATHINFO_FILENAME);
            $thumbnailName = 'thumbnails/' . $fileName . '_thumb.jpg';
            $thumbnailFullPath = Storage::disk('public')->path($thumbnailName);
            
            // Ensure thumbnails directory exists
            $thumbnailDir = dirname($thumbnailFullPath);
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            // Generate thumbnail at 2 seconds
            $frame = $video->frame(TimeCode::fromSeconds(2));
            $frame->save($thumbnailFullPath);

            return $thumbnailName;
        } catch (\Exception $e) {
            Log::error("Thumbnail generation failed: " . $e->getMessage());
            return null;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $media = EducationMedia::find($this->mediaId);
        
        if ($media) {
            $media->update([
                'processing_status' => 'failed',
                'processing_error' => $exception->getMessage()
            ]);
        }

        Log::error("Job failed permanently", [
            'media_id' => $this->mediaId,
            'error' => $exception->getMessage()
        ]);
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}