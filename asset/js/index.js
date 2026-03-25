const mobileMenu = document.getElementById("mobileMenu");
const hamburgerBtn = document.getElementById("hamburgerBtn");
const menuIconClosed = document.getElementById("menuIconClosed");
const menuIconOpen = document.getElementById("menuIconOpen");
const qrModal = document.getElementById("qrModal");
const openQrModal = document.getElementById("openQrModal");
const closeQrModal = document.getElementById("closeQrModal");
const tabUsd = document.getElementById("tabUsd");
const tabKhr = document.getElementById("tabKhr");
const qrImage = document.getElementById("qrImage");
const qrLabel = document.getElementById("qrLabel");

document.getElementById('contactForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = this.querySelector('button');
    const toast = document.getElementById('toast');
    const originalText = btn.dataset.originalText || btn.textContent;

    btn.dataset.originalText = originalText;
    btn.disabled = true;
    btn.textContent = "កំពុងផ្ញើ...";

    const formData = {
        name: document.getElementById('fullName').value,
        email: document.getElementById('contactEmail').value,
        subject: document.getElementById('subject').value,
        message: document.getElementById('message').value
    };

    try {
        const res = await fetch('send_to_telegram.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.ok !== true) {
            const detail = data.details ? ` ${data.details}` : "";
            throw new Error((data.error || "ការផ្ញើសារបរាជ័យ។ សូមសាកល្បងម្ដងទៀត។") + detail);
        }

        toast.classList.remove('translate-y-20', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');

        this.reset();

        setTimeout(() => {
            toast.classList.add('translate-y-20', 'opacity-0');
            toast.classList.remove('translate-y-0', 'opacity-100');
        }, 4000);
    } catch (err) {
        alert(err.message || "Error!");
    } finally {
        btn.disabled = false;
        btn.textContent = originalText;
    }
});

hamburgerBtn.addEventListener('click', () => {
    const isMenuClosed = mobileMenu.classList.contains('hidden');

    if (isMenuClosed) {
        mobileMenu.classList.remove('hidden');
        menuIconClosed.classList.add('hidden');
        menuIconOpen.classList.remove('hidden');
    } else {
        mobileMenu.classList.add('hidden');
        menuIconClosed.classList.remove('hidden');
        menuIconOpen.classList.add('hidden');
    }
});

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();

        if (!mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('hidden');
            menuIconClosed.classList.remove('hidden');
            menuIconOpen.classList.add('hidden');
        }

        const targetId = this.getAttribute('href');
        const targetElement = document.querySelector(targetId);

        const headerHeight = 64;
        const targetPosition = targetElement.getBoundingClientRect().top + window.scrollY;
        const offsetPosition = targetPosition - headerHeight;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    });
});

const setQrTab = (currency) => {
    if (currency === "USD") {
        tabUsd.classList.add("active");
        tabUsd.classList.remove("inactive");
        tabKhr.classList.add("inactive");
        tabKhr.classList.remove("active");
        qrImage.src = "aba_usd.png";
        qrImage.alt = "ABA QR USD";
        qrLabel.textContent = "ស្កេនជាមួយ ABA Mobile (USD)";
    } else {
        tabKhr.classList.add("active");
        tabKhr.classList.remove("inactive");
        tabUsd.classList.add("inactive");
        tabUsd.classList.remove("active");
        qrImage.src = "aba_khr.png";
        qrImage.alt = "ABA QR KHR";
        qrLabel.textContent = "ស្កេនជាមួយ ABA Mobile (KHR)";
    }
};

openQrModal.addEventListener("click", () => {
    qrModal.classList.add("active");
    qrModal.setAttribute("aria-hidden", "false");
});

closeQrModal.addEventListener("click", () => {
    qrModal.classList.remove("active");
    qrModal.setAttribute("aria-hidden", "true");
});

qrModal.addEventListener("click", (e) => {
    if (e.target === qrModal) {
        qrModal.classList.remove("active");
        qrModal.setAttribute("aria-hidden", "true");
    }
});

tabUsd.addEventListener("click", () => setQrTab("USD"));
tabKhr.addEventListener("click", () => setQrTab("KHR"));

document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && qrModal.classList.contains("active")) {
        qrModal.classList.remove("active");
        qrModal.setAttribute("aria-hidden", "true");
    }
});

document.getElementById('contactForm').addEventListener('submit', function (e) {
    setTimeout(() => {
        console.log("Form data collected. Simulated sending message to Niely.");
    }, 100);
});