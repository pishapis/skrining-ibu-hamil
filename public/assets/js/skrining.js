window.Fetch = class Fetch {
    constructor(urlString) {
        this.urlString = urlString;
        this.method = "GET";
        this.idElement = null;

        // untuk disable button
        this.disabled = false;
        this.disabledIdTarget = "";

        // untuk redirect
        this.redirect = false;
        this.redirectUrlBenar = "./";
        this.redirectUrlSalah = "./";
        this.redirectUrlWindow = "_self";

        // wait loader
        this.loader = true;

        // body Object
        this.bodyObject = null;
        this.request = null;
        this.xSrfCookie = null;
    }

    async run(idForm = null) {
        this.idForm = idForm;

        if (this.idForm) {
            console.log(
                "%c FETCHING DENGAN FORMULIR.. ",
                "background: #222; color: lime"
            );
            let headers = new Headers();

            // Ambil token CSRF dari cookie
            const cookieBrowser = `; ${document.cookie}`;
            const parts = cookieBrowser.split(`; XSRF-TOKEN=`);
            if (parts.length === 2) {
                const xSrfCookie = parts.pop().split(";").shift();
                this.xSrfCookie = xSrfCookie;
            }

            // Tambahkan token ke header
            if (this.xSrfCookie) {
                headers.append("XSRF-TOKEN", this.xSrfCookie);
            } else {
                ALERT("Kesalahan Server", "bad");
            }

            const idForm = this.idForm.replace("#", "");
            const formInput = document.getElementById(idForm);
            if (!formInput) {
                ALERT("Nama Formulir Salah", "bad");
                return { ack: "bad", message: "nama formulir salah" };
            }

            // Ambil data dari form
            const formData = new FormData(formInput);

            // Set request option
            const requestOption = {
                method: this.method.toUpperCase(),
                headers,
                body: formData,
            };
            const request = new Request(this.urlString, requestOption);
            this.request = request;
        }

        if (this.idForm == null) {
            let headers = new Headers();

            // Ambil csrf
            const cookieBrowser = `; ${document.cookie}`;
            const parts = cookieBrowser.split(`; e_tera_session=`);
            if (parts.length === 2) {
                const xSrfCookie = parts.pop().split(";").shift();
                this.xSrfCookie = xSrfCookie;
            }
            headers.append("CSRF", this.xSrfCookie);
            console.log(
                "%c FETCHING TANPA FORMULIR.. ",
                "background: #222; color: #bada55"
            );

            headers.append("Content-Type", "application/json");
            const requestOption = {
                method: this.method.toUpperCase(),
                headers,
            };

            // For GET requests, query parameters should be appended to the URL
            if (this.bodyObject) {
                const queryString = new URLSearchParams(
                    this.bodyObject
                ).toString();
                this.request = new Request(
                    `${this.urlString}?${queryString}`,
                    requestOption
                );
            } else {
                this.request = new Request(this.urlString, requestOption);
            }
        }

        if (this.disabled) {
            const id = this.disabledIdTarget.replace("#", "");
            const target = document.getElementById(id);
            if (target) {
                target.disabled = true;
            }
        }

        if (this.loader) {
            const overlayDiv = document.getElementById("overlayDiv");
            if (overlayDiv) {
                overlayDiv.classList.remove("hidden");
            }
        }

        const fetching = await fetch(this.request);
        if (!fetching.ok) {
            // Jika status tidak OK, berarti ada masalah
            throw new Error(`HTTP error! status: ${fetching.status}`);
        }
        // const responseText = await fetching.text();
        // console.log(responseText);
        const resultObj = await fetching.json();
        console.log(
            "%c 🚀 RESULT FETCHING " + this.urlString,
            "background: #FFFF00; color: #000",
            resultObj
        );

        if (this.disabled) {
            const id = this.disabledIdTarget.replace("#", "");
            const target = document.getElementById(id);
            if (target) {
                target.disabled = false;
            }
        }
        if (this.loader) {
            const overlayDiv = document.getElementById("overlayDiv");
            if (overlayDiv) {
                overlayDiv.classList.add("hidden");
            }
        }

        if (this.redirect) {
            if (resultObj.ack == "ok") {
                window.open(this.redirectUrlBenar, this.redirectUrlWindow);
            } else {
                ALERT("Gagal", "bad");
                setTimeout(() => {
                    window.open(this.redirectUrlSalah, this.redirectUrlWindow);
                }, 2500);
            }
        } else {
            // if (resultObj.ack !== "ok") {
            //     ALERT('Gagal ' + resultObj.message, "bad");
            // }
        }

        return resultObj;
    }
}

function formValidator(namaKelas) {
    const elements = document.querySelectorAll(
        `input[required].${namaKelas}, select[required].${namaKelas}, textarea[required].${namaKelas}`
    );
    let stop = false;
    [...elements].forEach((currentItem) => {
        // reseter
        const divValidasi = document.getElementById(
            "validator-" + currentItem.id
        );
        if (divValidasi) {
            divValidasi.innerHTML = "&nbsp;";
        }

        if (
            currentItem.value == "" ||
            typeof currentItem.value == "undefined" ||
            currentItem.value.length == 0
        ) {
            if (divValidasi) {
                divValidasi.innerText = "* wajib diisi";
            }
            stop = true;
            return false;
        }
        let panjangMinimal = parseInt(currentItem.getAttribute("required-min"));
        if (isNaN(panjangMinimal)) {
            panjangMinimal = 0;
        }
        if (currentItem.value.length < panjangMinimal) {
            if (divValidasi) {
                divValidasi.innerText = `isi minimal ${panjangMinimal} karakter`;
            }
            stop = true;
            return false;
        }

        let panjangMaximal = parseInt(currentItem.getAttribute("required-max"));
        if (isNaN(panjangMaximal)) {
            panjangMaximal = 9999;
        }
        if (currentItem.value.length > panjangMaximal) {
            if (divValidasi) {
                divValidasi.innerText = `isi maximal ${panjangMaximal} karakter`;
            }
            stop = true;
            return false;
        }

        if (currentItem.getAttribute("type") == "file") {
            if (typeof currentItem.files[0] === "undefined") {
                if (divValidasi) {
                    divValidasi.innerText = "* wajib upload file";
                }
                stop = true;
                return false;
            } else {
                if (currentItem.files[0].size > 50 * 1024 * 1024) {
                    if (divValidasi) {
                        divValidasi.innerText =
                            "* file tidak boleh lebih dari 10 MB";
                    }
                    stop = true;
                    return false;
                }
            }
        }
    });

    if (stop) {
        return false;
    }
    return true;
}

function firstCapital(val) {
    const str = val;
    const arr = str.split(" ");
    for (var i = 0; i < arr.length; i++) {
        arr[i] = arr[i].charAt(0).toUpperCase() + arr[i].slice(1);
    }
    const str2 = arr.join(" ");
    nama_pelanggan.value = str2;
}

delayTimer = null;

function delayedCariBanNew(input) {
    if (delayTimer) {
        clearTimeout(delayTimer);
    }
    delayTimer = setTimeout(function () {
        cariBanNew(input);
    }, 500);
}

function hideEl(idElement) {
    const element = document.getElementById(idElement);
    element.classList.remove("animate__zoomIn");
    element.classList.add("animate__animated");
    element.classList.add("animate__zoomOut");
}

function showEl(idElement) {
    const element = document.getElementById(idElement);
    element.classList.remove("animate__zoomOut");
    element.classList.add("animate__animated");
    element.classList.add("animate__zoomIn");
}

ALERT = function (message, type = "", opts = {}) {
  if (message == null) return;
  const text = String(message);
  const duration = Number(opts.duration ?? 3000);

  // holder (sekali buat)
  let holder = document.getElementById("alertPlaceholder");
  if (!holder) {
    holder = document.createElement("div");
    holder.id = "alertPlaceholder";
    holder.style.cssText = [
      "position:fixed", "top:12px", "right:12px",
      "display:flex", "flex-direction:column", "gap:8px",
      "z-index:2000", "pointer-events:none"
    ].join(";"); // container tidak menangkap klik
    document.body.appendChild(holder);
  }

  // mapping warna & ikon
  const key   = /ok/i.test(type) ? "ok" : /bad/i.test(type) ? "bad" : "info";
  const theme = {
    ok:   { bg: "#15803d", fg: "#fff", icon: "✓" },
    bad:  { bg: "#b91c1c", fg: "#fff", icon: "⚠" },
    info: { bg: "#f59e0b", fg: "#000", icon: "ℹ" },
  }[key];

  // buat satu toast
  const toast = document.createElement("div");
  toast.role = "status";
  toast.style.cssText = [
    "pointer-events:auto", // biar bisa diklik
    `background:${theme.bg}`, `color:${theme.fg}`,
    "padding:.5rem .75rem", "border-radius:.5rem",
    "box-shadow:0 10px 15px rgba(0,0,0,.1)",
    "display:flex", "align-items:center", "gap:.5rem",
    "max-width: min(90vw, 28rem)"
  ].join(";");
  toast.className = "animate__animated animate__fadeInDown";

  // aman dari HTML injection
  const msgEl = document.createElement("span");
  msgEl.textContent = text;

  const iconEl = document.createElement("span");
  iconEl.setAttribute("aria-hidden", "true");
  iconEl.textContent = theme.icon;

  toast.appendChild(iconEl);
  toast.appendChild(msgEl);
  holder.appendChild(toast);

  // fungsi tutup (animasi keluar)
  const close = () => {
    toast.classList.remove("animate__fadeInDown");
    toast.classList.add("animate__fadeOutUp");
    setTimeout(() => toast.remove(), 350);
  };

  // auto-close + klik untuk tutup
  const timer = setTimeout(close, duration);
  toast.addEventListener("click", () => { clearTimeout(timer); close(); });
};


function modifierClass(identifier, jenis, classs) {
    if (jenis == "add") {
        method = "add";
    } else if (jenis == "remove") {
        method = "remove";
    } else {
        console.log("PROGRAMER: JENIS SALAH");
        return;
    }

    const classArray = classs
        .replace(/^\s+/, "")
        .replace(/\s+$/, "")
        .replace(/\s+/, " ")
        .split(" ");

    if (typeof identifier === "object") {
        const elements = identifier;
        if (elements) {
            if (Array.from(elements).length > 1) {
                [...elements].forEach((element) => {
                    classArray.forEach((class_) => {
                        element.classList[method](class_);
                    });
                });
            } else {
                classArray.forEach((class_) => {
                    identifier.classList[method](class_);
                });
            }
        }
    } else if (identifier.search(/^#/) >= 0) {
        const element = document.querySelector(identifier);
        if (element) {
            classArray.forEach((class_) => {
                element.classList[method](class_);
            });
        }
    } else if (identifier.search(/^\./) >= 0) {
        const elements = document.querySelectorAll(identifier);
        if (elements) {
            [...elements].forEach((element) => {
                classArray.forEach((class_) => {
                    element.classList[method](class_);
                });
            });
        }
    } else {
        console.log("PROGRAMER: SELECTOR SALAH");
    }
}

async function jsConfirm() {
    modifierClass("#divJsConfirm", "remove", "hidden");

    return new Promise((resolve, reject) => {
        const batalKonfirmasi = document.getElementById("batalKonfirmasi");
        const yakinKonfirmasi = document.getElementById("yakinKonfirmasi");

        yakinKonfirmasi.addEventListener("click", function () {
            modifierClass("#divJsConfirm", "add", "hidden");
            resolve(true);
            return;
        });
        batalKonfirmasi.addEventListener("click", function () {
            modifierClass("#divJsConfirm", "add", "hidden");
            resolve(false);
        });
    });
}

async function jsConfirmPenjumlahan(tanya) {
    modifierClass("#divJsConfirm", "remove", "hidden");
    document.getElementById("jawabanC").focus();

    return new Promise((resolve, reject) => {
        const batalKonfirmasi = document.getElementById("batalKonfirmasi");
        const yakinKonfirmasi = document.getElementById("yakinKonfirmasi");

        a = Math.floor(Math.random() * 10);
        if (a == 0) {
            a = 1;
        }
        b = Math.floor(Math.random() * 10);
        if (b == 0) {
            b = 1;
        }
        c = parseInt(a) + parseInt(b);
        document.getElementById("pertanyaanA").innerText = a;
        document.getElementById("pertanyaanB").innerText = b;
        document.getElementById("jawabanC").value = "";

        const input = document.getElementById("jawabanC");
        input.addEventListener("keypress", function (event) {
            if (event.key === "Enter") {
                event.preventDefault();
                document.getElementById("yakinKonfirmasi").click();
            }
        });

        yakinKonfirmasi.addEventListener("click", function () {
            const jawaban = document.getElementById("jawabanC").value;
            if (jawaban == c) {
                modifierClass("#divJsConfirm", "add", "hidden");
                resolve(true);
                return;
            } else {
                modifierClass("#divJsConfirm", "add", "hidden");
                resolve(false);
                return;
            }
        });
        batalKonfirmasi.addEventListener("click", function () {
            resolve(false);
        });
    });
}

function tutupDivJsConfirm() {
    modifierClass("#divJsConfirm", "add", "hidden");
}

function closeModal(idmodal, idtransisi) {
    // hideEl(idtransisi);
    if (idmodal) {
        modifierClass(`#${idtransisi}`, "remove", "animate__fadeIn");
        modifierClass(`#${idtransisi}`, "add", "animate__fadeOut");
        setTimeout(() => {
            modifierClass(`#${idmodal}`, "add", "hidden");
        }, 100);
    }
}

function openModal(idmodal, idtransisi) {
    // validasiAnimasi() = "";
    modifierClass(`#${idtransisi}`, "remove", "animate__fadeOut");
    modifierClass(`#${idtransisi}`, "add", "animate__fadeIn");

    // showEl(idtransisi);
    // modifierClass(`#${idtransisi}`, "add", "h-[calc(100vh-20px)]");
    modifierClass(`#${idmodal}`, "remove", "hidden");
}

function showMainMenu(idElement, elm, event) {
    let stop = false;
    const containerMenu = document.getElementById(idElement);
    burger1 = document.getElementById("burger");
    burger2 = document.getElementById("burger2");
    hideMenuClickOutside();

    if (containerMenu?.className.search("hiddenMainMenu") >= 0) {
        modifierClass("#containerMenu", "remove", "hiddenMainMenu");
        modifierClass("#containerMenu", "add", "showMainMenu");

        burger1.style.animation = "mencolotAnimation 0.5s";
        setTimeout(function () {
            burger1.style.animation = "";
            modifierClass("#burger", "add", "hidden");
            modifierClass("#burger2", "remove", "hidden");
            modifierClass(
                "#burger2",
                "add",
                "animate__animated animate__bounceIn"
            );
        }, 200);
    } else {
        modifierClass("#containerMenu", "remove", "showMainMenu");
        modifierClass("#containerMenu", "add", "hiddenMainMenu");
        burger2.style.animation = "mencolotAnimation 0.5s";
        setTimeout(function () {
            burger2.style.animation = "";
            modifierClass("#burger2", "add", "hidden");
            modifierClass("#burger", "remove", "hidden");
            modifierClass(
                "#burger",
                "add",
                "animate__animated animate__bounceIn"
            );

            // netralkan tutup semua submenu
            modifierClass(".submenu", "remove", "subMenuShow");
            modifierClass(".submenu", "add", "subMenuHide");

            // netralkan semua button blur
            const semuaButtonMenu =
                document.querySelectorAll("div#isiMenu button");
            [...semuaButtonMenu].forEach((element) => {
                element.classList.add("text-purple-100");
                element.classList.remove("text-purple-500");
                element.classList.remove("blur");
            });
        }, 200);
    }
}

// untuk menghilangkan menu ketika klik di luar menu
function hideMenuClickOutside() {
    const selaimContainerMenuElements =
        document.querySelectorAll("div.container");
    [...selaimContainerMenuElements].forEach((element) => {
        element.addEventListener("click", function (event) {
            event.stopPropagation();
            modifierClass("#containerMenu", "remove", "showMainMenu");
            modifierClass("#containerMenu", "add", "hiddenMainMenu");

            burger2 = document.getElementById("burger2");
            burger2.style.animation = "";
            modifierClass("#burger2", "add", "hidden");
            modifierClass("#burger", "remove", "hidden");
            modifierClass(
                "#burger",
                "add",
                "animate__animated animate__bounceIn"
            );
        });
    });
}

function subMenuToggle(idElement, elementIni) {
    const buttonIni = elementIni;

    if (buttonIni?.className.search("blur") >= 0) {
        console.log("button ini sudah di blur");
    }

    const semuaButtonMenu = document.querySelectorAll("div#isiMenu button");
    [...semuaButtonMenu].forEach((element) => {
        element.classList.remove("text-purple-100");
        element.classList.add("text-purple-500");
        element.classList.add("blur");
    });
    const semuaButtonMenuTerpilih = document.querySelectorAll(
        `div#${idElement} button`
    );
    [...semuaButtonMenuTerpilih].forEach((element) => {
        element.classList.add("text-purple-100");
        element.classList.remove("text-purple-500");
        element.classList.remove("blur");
    });
    elementIni.classList.add("text-purple-100");
    elementIni.classList.remove("text-purple-500");
    elementIni.classList.remove("blur");

    // buka menu yang sedang di pilih
    const containerMenu = document.getElementById(idElement);
    if (containerMenu?.className.search("subMenuHide") >= 0) {
        // nutup semua menu dulu
        modifierClass(".submenu", "remove", "subMenuShow");
        modifierClass(".submenu", "add", "subMenuHide");

        modifierClass(containerMenu, "remove", "subMenuHide");
        modifierClass(containerMenu, "add", "subMenuShow");
    } else {
        modifierClass(containerMenu, "remove", "subMenuShow");
        modifierClass(containerMenu, "add", "subMenuHide");

        // netralkan semua button menu blur

        [...semuaButtonMenu].forEach((element) => {
            element.classList.add("text-purple-100");
            element.classList.remove("text-purple-500");
            element.classList.remove("blur");
        });
    }
}

function validasiAnimasi() {
    const inputSelectElements = document.querySelectorAll("input[required]");
    [...inputSelectElements].forEach((currentItem) => {
        currentItem.addEventListener("keyup", function () {
            if (typeof timer === "undefined") {
                timer = "";
            }
            clearTimeout(timer);
            timer = setTimeout(function () {
                // reseter
                const divValidasi = document.getElementById(
                    "validator-" + currentItem.id
                );
                divValidasi.innerHTML = "&nbsp;";
                let text = "";

                let panjangMinimal = parseInt(
                    currentItem.getAttribute("required-min")
                );
                if (isNaN(panjangMinimal)) {
                    panjangMinimal = 0;
                }
                if (currentItem.value.length < panjangMinimal) {
                    text = `isi minimal ${panjangMinimal} karakter`;
                }
                let panjangMaximal = parseInt(
                    currentItem.getAttribute("required-max")
                );
                if (isNaN(panjangMaximal)) {
                    panjangMaximal = 9999;
                }
                if (currentItem.value.length > panjangMaximal) {
                    text = `isi maximal ${panjangMaximal} karakter`;
                }
                if (currentItem.value == "") {
                    text = `* wajib diisi`;
                }

                let index = 0;

                function typing() {
                    const textString = text.toString();
                    if (index < textString.length) {
                        divValidasi.innerHTML =
                            textString.slice(0, index) +
                            '<span class="blinking-cursor">|</span>';
                        index++;
                        setTimeout(typing, Math.random() * 90);
                    } else {
                        divValidasi.innerHTML = textString.slice(0, index) + "";
                    }
                }
                typing();
            }, 800);
        });
    });
}

rp = function (angka, prefix) {
    if (parseInt(angka) == NaN) {
        return 0;
    }
    if (parseInt(angka) == 0) {
        return 0;
    }

    if (!angka) {
        return;
    }
    minus = false;
    if (angka < 0) {
        minus = true;
        angka = Math.abs(angka);
    }
    (numberString = angka.toString()),
        (split = numberString.split(",")),
        (sisa = split[0].length % 3),
        (rupiah = split[0].substr(0, sisa)),
        (ribuan = split[0].substr(sisa).match(/\d{3}/gi));

    // tambahkan titik jika yang di input sudah menjadi angka ribuan
    if (ribuan) {
        separator = sisa ? "." : "";
        rupiah += separator + ribuan.join(".");
    }

    rupiah = split[1] != undefined ? rupiah + "," + split[1] : rupiah;

    if (minus) {
        return prefix == undefined
            ? "-" + rupiah
            : rupiah
            ? "Rp. -" + rupiah
            : "";
    }
    return prefix == undefined ? rupiah : rupiah ? "Rp. " + rupiah : "";
};

function limitString(content, limit, suffix = ' ...') {
    if (content.length > limit) {
        return content.substring(0, limit) + suffix;
    }
    return content;
}
// penggunaan searchBar gunakan fungsi feliksEncode untuk parsing data dari Mongo(object) ke javascript
// $feliksEncode = json_encode($datatabelDb);
// $feliksEncode = str_replace('"', '@#$', $feliksEncode);

function searchBar(event, idTR, dataDariPhp) {
    let data = dataDariPhp.replaceAll("@#$", '"');
    let input = event.target.value;
    for (let i = 1; i <= data.length; i++) {
        const carimerk = document.getElementById(idTR + i);
        const data = carimerk?.dataset.isi?.toLocaleLowerCase();
        if (data?.indexOf(input.toLocaleLowerCase()) === -1) {
            document.getElementById(idTR + i)?.classList.add("hidden");
        } else {
            document.getElementById(idTR + i)?.classList.remove("hidden");
        }
    }
}

function searchBar2(idInputSearchBar, idTR) {
    let input = document.getElementById(idInputSearchBar).value;

    allTR = document.querySelectorAll("tr.trIsi");
    i = 0;
    for (tr of allTR) {
        i++;
        const dataset = tr?.dataset.isi?.toLocaleLowerCase();
        if (dataset?.indexOf(input.toLocaleLowerCase()) === -1) {
            document.getElementById(idTR + i)?.classList.add("hidden");
        } else {
            document.getElementById(idTR + i)?.classList.remove("hidden");
        }
    }
}

function setValue(idinput, value) {
    document.getElementById(idinput).value = value;
}

function groupBy(array, key) {
    return array.reduce((result, currentValue) => {
        (result[currentValue[key]] = result[currentValue[key]] || []).push(
            currentValue
        );
        return result;
    }, {});
}

function formatDecimal(input) {
    let value = input.value;
    value = value.replace(/[^\d]/g, "");

    if (!value) {
        input.value = "";
        return;
    }
    if (value.length <= 2) {
        input.value = value;
    } else {
        let integerPart = value.slice(0, value.length - 2); // Bagian sebelum titik desimal
        let decimalPart = value.slice(value.length - 2); // Bagian setelah titik desimal
        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        input.value = `${integerPart}.${decimalPart}`;
    }
}

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".currency").forEach(function (field) {
        field.addEventListener("input", function () {
            formatDecimal(field); // Panggil fungsi formatDecimal
        });
    });
});

//RANDOM VALUE
word = function (length) {
    let result = "";
    const characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    const charactersLength = characters.length;
    let counter = 0;
    while (counter < length) {
        result += characters.charAt(
            Math.floor(Math.random() * charactersLength)
        );
        counter += 1;
    }
    return result;
};

number = function (length) {
    let result = "";
    const characters = "0123456789";
    const charactersLength = characters.length;
    let counter = 0;
    while (counter < length) {
        result += characters.charAt(
            Math.floor(Math.random() * charactersLength)
        );
        counter += 1;
    }
    return result;
};

wordNumber = function (length) {
    let result = "";
    const characters =
        "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    const charactersLength = characters.length;
    let counter = 0;
    while (counter < length) {
        result += characters.charAt(
            Math.floor(Math.random() * charactersLength)
        );
        counter += 1;
    }
    return result;
};

specialchars = function (length) {
    let result = "";
    const characters = "!@#$%^&*()_-+={}[]|;:<>,.?~";
    const charactersLength = characters.length;
    let counter = 0;
    while (counter < length) {
        result += characters.charAt(
            Math.floor(Math.random() * charactersLength)
        );
        counter += 1;
    }
    return result;
};

wordSpecialchars = function (length) {
    let result = "";
    const characters =
        "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!@#$%^&*()_-+={}[]|;:<>,.?~";
    const charactersLength = characters.length;
    let counter = 0;
    while (counter < length) {
        result += characters.charAt(
            Math.floor(Math.random() * charactersLength)
        );
        counter += 1;
    }
    return result;
};

wordNumberSpecialchars = function (length) {
    let result = "";
    const characters =
        "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!@#$%^&*()_-+={}[]|;:<>,.?~0123456789";
    const charactersLength = characters.length;
    let counter = 0;
    while (counter < length) {
        result += characters.charAt(
            Math.floor(Math.random() * charactersLength)
        );
        counter += 1;
    }
    return result;
};
//TUTUP RANDOM VALUE

function blobToDataURL(blob) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = () => reject(reader.error);
        reader.onabort = () =>
            reject(new Error("Pembacaan blob dibatalkan user"));
        reader.readAsDataURL(blob);
    });
}

function IMGJS(
    idDiv,
    PUBLIC_ROOT,
    namaFolder,
    namaFile,
    idFile = "",
    classs = "",
    altImage = ""
) {
    const div = document.getElementById(idDiv);
    div.innerHTML = `
    <picture>
      <source media='(min-width:1600px)' srcset='${PUBLIC_ROOT}/FOTO/${namaFolder}/index.php?file=${namaFile}&size=1800'>
      <source media='(min-width:1400px)' srcset='${PUBLIC_ROOT}/FOTO/${namaFolder}/index.php?file=${namaFile}&size=1600'>
      <source media='(min-width:1200px)' srcset='${PUBLIC_ROOT}/FOTO/${namaFolder}/index.php?file=${namaFile}&size=1400'>
      <source media='(min-width:1000px)' srcset='${PUBLIC_ROOT}/FOTO/${namaFolder}/index.php?file=${namaFile}&size=1200'>
      <source media='(min-width:800px)' srcset='${PUBLIC_ROOT}/FOTO/${namaFolder}/index.php?file=${namaFile}&size=1000'>
      <source media='(min-width:600px)' srcset='${PUBLIC_ROOT}/FOTO/${namaFolder}/index.php?file=${namaFile}&size=800'>
      <source media='(min-width:400px)' srcset='${PUBLIC_ROOT}/FOTO/${namaFolder}/index.php?file=${namaFile}&size=600'>
      <source media='(min-width:200px)' srcset='${PUBLIC_ROOT}/FOTO/${namaFolder}/index.php?file=${namaFile}&size=400'>
      <img id='${idFile}' class='${classs} cursor-pointer' src='${PUBLIC_ROOT}/FOTO/${namaFolder}/index.php?file=${namaFile}&type=full' alt='${altImage}' >
    </picture>`;
}

function IMGJS2(
    idDiv,
    PUBLIC_ROOT,
    namaFolder,
    namaFile,
    idFile = "",
    classs = "",
    altImage = ""
) {
    const div = document.getElementById(idDiv);
    div.innerHTML = `
    <picture>
      <img id='${idFile}' class='${classs} cursor-pointer' src='${PUBLIC_ROOT}/FOTO/${namaFolder}/${namaFile}' alt='${altImage}' >
    </picture>`;
}

function IMGJSUPLOADS2(
    idDiv,
    PUBLIC_ROOT,
    namaFolder,
    namaFile,
    idFile = "",
    classs = "",
    altImage = ""
) {
    const div = document.getElementById(idDiv);
    div.innerHTML = `
    <picture>
      <img id='${idFile}' class='${classs} cursor-pointer' src='${PUBLIC_ROOT}/UPLOADS/${namaFolder}/${namaFile}' alt='${altImage}' >
    </picture>`;
}

function tanggal_yyyymmdd(arg1) {
    let monthJadi = "";
    let dateJadi = "";
    const offset = new Date().getTimezoneOffset() * 60000;

    const d = new Date(new Date(arg1).getTime());
    const year = d.getFullYear();
    const month = d.getMonth() + 1;
    const date = d.getDate();
    if (month < 10) {
        monthJadi = "0" + month;
    } else {
        monthJadi = month.toString();
    }
    if (date < 10) {
        dateJadi = "0" + date;
    } else {
        dateJadi = date.toString();
    }
    const yyyymmddjadi =
        year.toString() + "-" + monthJadi.toString() + "-" + dateJadi;
    return yyyymmddjadi;
}

function datetimeLocal_yyyymmddhhmm(arg1) {
    let monthJadi = "";
    let dateJadi = "";
    let hoursJadi = "";
    let minutesJadi = "";

    const offset = new Date().getTimezoneOffset() * 60000;

    const d = new Date(new Date(arg1).getTime());
    const year = d.getFullYear();
    const month = d.getMonth() + 1;
    const date = d.getDate();
    const hours = d.getHours();
    const minutes = d.getMinutes();

    if (month < 10) {
        monthJadi = "0" + month;
    } else {
        monthJadi = month.toString();
    }

    if (date < 10) {
        dateJadi = "0" + date;
    } else {
        dateJadi = date.toString();
    }

    if (hours < 10) {
        hoursJadi = "0" + hours;
    } else {
        hoursJadi = hours.toString();
    }

    if (minutes < 10) {
        minutesJadi = "0" + minutes;
    } else {
        minutesJadi = minutes.toString();
    }

    const datetimeLocalString = `${year}-${monthJadi}-${dateJadi}T${hoursJadi}:${minutesJadi}`;
    return datetimeLocalString;
}

function fTotalHari(tglAwal, tglAkhir){
    const dateAwal = new Date(tglAwal);
    const dateAkhir = new Date(tglAkhir);
    const selisihWaktu = dateAkhir.getTime() - dateAwal.getTime();
    const totalHari = selisihWaktu / (1000 * 60 * 60 * 24);
    return totalHari;
}

function formatDate(date) {
    var d = new Date(parseInt(date)),
        month = "" + (d.getMonth() + 1),
        day = "" + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) month = "0" + month;
    if (day.length < 2) day = "0" + day;

    return [year, month, day].join("-");
}

function formatTahunBulan(date) {
    var d = new Date(parseInt(date)),
        month = "" + (d.getMonth() + 1),
        year = d.getFullYear();

    if (month.length < 2) {
        month = "0" + month;
    }
    return [year, month].join("-");
}

function get_tanggal(tanggal) {
    const timestamp = parseInt(tanggal);
    const date = new Date(timestamp);
    const monthNames = [
        "Januari",
        "Februari",
        "Maret",
        "April",
        "Mei",
        "Juni",
        "Juli",
        "Agustus",
        "September",
        "Oktober",
        "November",
        "Desember",
    ];
    const day = date.getDate();
    let dayjadi = "";
    if (day.toString().length == 1) {
        dayjadi = "0" + day;
    } else {
        dayjadi = day;
    }
    const month = monthNames[date.getMonth()];
    const year = date.getFullYear();
    const formattedDate = `${dayjadi} ${month} ${year}`;
    return formattedDate;
}
function get_tanggal2(tanggal) {
    const timestamp = parseInt(tanggal);
    const date = new Date(timestamp);
    const monthNames = [
        "01",
        "02",
        "03",
        "04",
        "05",
        "06",
        "07",
        "08",
        "09",
        "10",
        "11",
        "12",
    ];
    const day = date.getDate();
    let dayjadi = "";
    if (day.toString().length == 1) {
        dayjadi = "0" + day;
    } else {
        dayjadi = day;
    }
    const month = monthNames[date.getMonth()];
    const year = date.getFullYear();
    const formattedDate = `${dayjadi}/${month}/${year}`;
    return formattedDate;
}

function buttonActive(buttonActive, buttonNonActive) {
    const button = document.getElementById(buttonActive);

    modifierClass(button, "remove", "bg-gray-700 text-white");
    modifierClass(button, "add", "bg-gray-800 ");
    button.setAttribute("disabled", "");

    const buttonNot = buttonNonActive.split(",");

    for (let i = 0; i < buttonNot.length; i++) {
        const notActive = document.getElementById(buttonNot[i]);
        modifierClass(notActive, "remove", "bg-gray-800 ");
        modifierClass(notActive, "add", "bg-gray-700 text-white");
        notActive.removeAttribute("disabled");
    }
}

async function file_get_contents(uri, callback) {
    let res = await fetch(uri),
        ret = await res.text();
    return callback ? callback(ret) : ret; // a Promise() actually.
}

function rtrim(input, karakter = " ") {
    let output = input;
    let regex = new RegExp(karakter + "+$", "ig");
    output = output.replace(regex, "");
    return output;
}

function ltrim(input, karakter = " ") {
    let output = input;
    let regex = new RegExp("^" + karakter + "+", "ig");
    output = output.replace(regex, "");
    return output;
}

function trim(input, karakter = " ") {
    let output = input;
    let regex = new RegExp(karakter + "+$", "ig");
    output = output.replace(regex, "");
    regex = new RegExp("^" + karakter + "+", "ig");
    output = output.replace(regex, "");
    return output;
}

function keyUPHilangkanSpace(elm, event) {
    elm.value = elm.value.replace(/\s/, "");
}

function ubahFormatTanggal(tgl) {
    const date = new Date(tgl);
    const month = (date.getMonth() + 1).toString().padStart(2, "0");
    const year = date.getFullYear();
    const day = date.getDay();
    // let dayjadi = "";
    // if (day.toString().length == 1) {
    //     dayjadi = "0" + day;
    // } else {
    //     dayjadi = day;
    // }
    const hasil = `${year}-${month}-${day}`;
    return hasil;
}

function formatDateInput(date) {
    var d = new Date(parseInt(date)),
        month = "" + (d.getMonth() + 1),
        day = "" + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) month = "0" + month;
    if (day.length < 2) day = "0" + day;

    return [year, month, day].join("-");
}

function get_tanggaldanjam2(tanggal) {
    const timestamp = parseInt(tanggal);
    const date = new Date(timestamp);
    const monthNames = [
        "01",
        "02",
        "03",
        "04",
        "05",
        "06",
        "07",
        "08",
        "09",
        "10",
        "11",
        "12",
    ];
    const day = date.getDate();
    let dayjadi = "";
    if (day.toString().length == 1) {
        dayjadi = "0" + day;
    } else {
        dayjadi = day;
    }
    const month = monthNames[date.getMonth()];
    const year = date.getFullYear();
    const hours = date.getHours();
    const minutes = date.getMinutes();
    const seconds = date.getSeconds();

    const formattedDate = `${dayjadi}/${month}/${year} - ${hours
        .toString()
        .padStart(2, "0")}:${minutes.toString().padStart(2, "0")}`;
    return formattedDate;
}

function get_tanggaldanjam(tanggal) {
    const timestamp = parseInt(tanggal);
    const date = new Date(timestamp);
    date.setHours(date.getHours() - 7);
    const monthNames = [
        "Januari",
        "Februari",
        "Maret",
        "April",
        "Mei",
        "Juni",
        "Juli",
        "Agustus",
        "September",
        "Oktober",
        "November",
        "Desember",
    ];
    const day = date.getDate();
    let dayjadi = "";
    if (day.toString().length == 1) {
        dayjadi = "0" + day;
    } else {
        dayjadi = day;
    }
    const month = monthNames[date.getMonth()];
    const year = date.getFullYear();
    const hours = date.getHours();
    const minutes = date.getMinutes();
    const seconds = date.getSeconds();

    const formattedDate = `${dayjadi} ${month} ${year} ${hours
        .toString()
        .padStart(2, "0")}:${minutes.toString().padStart(2, "0")}`;
    return formattedDate;
}

function getJam(dateJam) {
    const timestamp = parseInt(dateJam);
    const jam = new Date(timestamp);
    const hours = jam.getHours();
    const minutes = jam.getMinutes();

    const formattedJam = `${hours.toString().padStart(2, "0")}:${minutes
        .toString()
        .padStart(2, "0")}`;
    return formattedJam;
}

window.levenshteinDistance = (s, t) => {
    // if (!s) {return;}
    // if (!t) {return;}
    // if (!s.length) return t.length;
    // if (!t.length) return s.length;

    const arr = [];
    for (let i = 0; i <= t.length; i++) {
        arr[i] = [i];
        for (let j = 1; j <= s.length; j++) {
            arr[i][j] =
                i === 0
                    ? j
                    : Math.min(
                          arr[i - 1][j] + 1,
                          arr[i][j - 1] + 1,
                          arr[i - 1][j - 1] + (s[j - 1] === t[i - 1] ? 0 : 1)
                      );
        }
    }
    return arr[t.length][s.length]; // output : number
};

function oninputKeterangan(element, event) {
    const thiselement = event.target;
    thiselement.style.height = "auto";
    thiselement.style.height = thiselement.scrollHeight + "px";
}

function ribuanDecimal(hasil) {
    const angkaFormatted = hasil.toLocaleString('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    return angkaFormatted;
}

function format_custom_decimal_id(number) {
    if (isNaN(number) || number === undefined || number === null) {
        return 0;
    }

    // Ensure the number is a float and round to two decimal places
    let formattedNum = parseFloat(number).toFixed(2);

    // Split the number into integer and decimal parts
    let parts = formattedNum.split('.');
    let integerPart = parts[0];
    let decimalPart = parts[1];

    // Add thousand separators to the integer part
    integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    // Combine the integer part with the decimal part
    return `${integerPart},${decimalPart}`;
}

function format_tanggal(dateString) {
    const bulanIndo = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];

    let [tahun, bulan, hari] = dateString.split('-');
    let namaBulan = bulanIndo[parseInt(bulan, 10) - 1];
    return `${hari} ${namaBulan} ${tahun}`;
}
