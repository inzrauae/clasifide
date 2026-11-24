import Alpine from 'alpinejs';

// Sinhala pages put their translations in window.i18n (see layouts/app.blade.php)
const t = (text) => (window.i18n && window.i18n[text]) || text;

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

const post = (url) =>
    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });

// Shrink phone photos before upload (saves mobile data and server space)
const compress = (file, maxSide = 1600) =>
    new Promise((resolve) => {
        if (!file.type.startsWith('image/') || file.size < 400 * 1024) return resolve(file);
        const img = new Image();
        img.onload = () => {
            const scale = Math.min(1, maxSide / Math.max(img.width, img.height));
            const c = document.createElement('canvas');
            c.width = Math.round(img.width * scale);
            c.height = Math.round(img.height * scale);
            c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
            c.toBlob((b) => resolve(b ? new File([b], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : file), 'image/jpeg', 0.85);
            URL.revokeObjectURL(img.src);
        };
        img.onerror = () => resolve(file);
        img.src = URL.createObjectURL(file);
    });

// Nearest district centre to the visitor's position
const nearest = (lat, lng, districts) => {
    let best = null;
    let bestD = Infinity;
    for (const [name, [dLat, dLng]] of Object.entries(districts)) {
        const d = (lat - dLat) ** 2 + ((lng - dLng) * Math.cos((lat * Math.PI) / 180)) ** 2;
        if (d < bestD) {
            bestD = d;
            best = name;
        }
    }
    return best;
};

document.addEventListener('alpine:init', () => {
    Alpine.store('toast', {
        msg: '',
        t: null,
        show(m) {
            this.msg = m;
            clearTimeout(this.t);
            this.t = setTimeout(() => (this.msg = ''), 3200);
        },
    });

    // "Show number" button: reveals the phone and records the lead
    Alpine.data('phoneReveal', (url, masked) => ({
        label: masked,
        tel: null,
        busy: false,
        async reveal() {
            if (this.tel) return (window.location.href = this.tel);
            this.busy = true;
            try {
                const r = await post(url);
                const j = await r.json();
                this.label = j.phone;
                this.tel = j.tel;
            } catch (e) {
                Alpine.store('toast').show(t('Could not load the number. Try again.'));
            }
            this.busy = false;
        },
    }));

    Alpine.data('favorite', (url, saved, loggedIn, loginUrl) => ({
        saved,
        async toggle() {
            if (!loggedIn) return (window.location.href = loginUrl);
            this.saved = !this.saved;
            try {
                const r = await post(url);
                const j = await r.json();
                this.saved = j.saved;
                Alpine.store('toast').show(t(j.saved ? 'Saved to your favourites' : 'Removed from favourites'));
            } catch (e) {
                this.saved = !this.saved;
            }
        },
    }));

    Alpine.data('share', (title) => ({
        async go() {
            const url = window.location.href;
            if (navigator.share) {
                try {
                    await navigator.share({ title, url });
                } catch (e) {}
                return;
            }
            await navigator.clipboard?.writeText(url);
            Alpine.store('toast').show(t('Link copied'));
        },
    }));

    // Location picker with "use my location"
    Alpine.data('locationPicker', (districts, current) => ({
        open: false,
        q: '',
        current,
        locating: false,
        get list() {
            return Object.keys(districts).filter((d) => d.toLowerCase().includes(this.q.toLowerCase()));
        },
        nearMe(onPick) {
            if (!navigator.geolocation) return Alpine.store('toast').show(t('Location is not available on this device'));
            this.locating = true;
            navigator.geolocation.getCurrentPosition(
                (p) => {
                    this.locating = false;
                    const d = nearest(p.coords.latitude, p.coords.longitude, districts);
                    Alpine.store('toast').show(t('Showing ads near you in :place').replace(':place', d));
                    onPick(d);
                },
                () => {
                    this.locating = false;
                    Alpine.store('toast').show(t('Allow location access to find ads near you'));
                },
                { timeout: 8000, maximumAge: 600000 }
            );
        },
    }));

    // Photo picker on the post-ad form
    Alpine.data('photoPicker', (max, existing) => ({
        files: [],
        previews: [],
        existing,
        removed: [],
        get count() {
            return this.files.length + this.existing.length - this.removed.length;
        },
        async add(e) {
            const room = max - this.count;
            const picked = Array.from(e.target.files || []).slice(0, Math.max(0, room));
            for (const f of picked) {
                const c = await compress(f);
                this.files.push(c);
                this.previews.push(URL.createObjectURL(c));
            }
            e.target.value = '';
            this.sync();
        },
        drop(i) {
            URL.revokeObjectURL(this.previews[i]);
            this.files.splice(i, 1);
            this.previews.splice(i, 1);
            this.sync();
        },
        toggleExisting(id) {
            this.removed.includes(id) ? (this.removed = this.removed.filter((x) => x !== id)) : this.removed.push(id);
        },
        sync() {
            const dt = new DataTransfer();
            this.files.forEach((f) => dt.items.add(f));
            this.$refs.real.files = dt.files;
        },
    }));

    // Post-ad form: category-driven fields and cities
    Alpine.data('adForm', (cfg) => ({
        cat: cfg.cat,
        district: cfg.district,
        city: cfg.city,
        cats: cfg.cats,
        cities: cfg.cities,
        get category() {
            return this.cats[this.cat] || null;
        },
        get cityList() {
            return this.cities[this.district] || [];
        },
    }));

    Alpine.data('gallery', (n) => ({
        i: 0,
        n,
        go(k) {
            this.i = (k + this.n) % this.n;
            const el = this.$refs.strip;
            el.scrollTo({ left: this.i * el.clientWidth, behavior: 'smooth' });
        },
        onScroll(e) {
            const el = e.target;
            this.i = Math.round(el.scrollLeft / Math.max(1, el.clientWidth));
        },
    }));
});

window.Alpine = Alpine;
Alpine.start();

if ('serviceWorker' in navigator && location.protocol === 'https:') {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}
