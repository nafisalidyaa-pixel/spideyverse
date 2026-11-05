<svg class="logo-spider" viewBox="0 0 72 72" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" role="img" aria-label="Kepala Spider-Man">
    <defs>
        <linearGradient id="helmSpider" x1="0.18" y1="0" x2="0.82" y2="1">
            <stop offset="0" stop-color="#ff7a5c"/>
            <stop offset="0.18" stop-color="#f23f4d"/>
            <stop offset="0.5" stop-color="#d61f2c"/>
            <stop offset="0.82" stop-color="#a3121d"/>
            <stop offset="1" stop-color="#7f0f16"/>
        </linearGradient>
        <linearGradient id="mataSpider" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#ffffff"/>
            <stop offset="1" stop-color="#dbe4ec"/>
        </linearGradient>
        <path id="bentukHelm" d="M36 3C22.6 3 11.5 14.9 11.5 29.6c0 10.6 4.9 18.7 12.1 23.6 4.8 3.2 7.2 7 12.4 8.9 5.2-1.9 7.6-5.7 12.4-8.9 7.2-4.9 12.1-13 12.1-23.6C60.5 14.9 49.4 3 36 3Z"/>
        <clipPath id="maskClip">
            <use xlink:href="#bentukHelm"/>
        </clipPath>
        <ellipse id="mataKiri" cx="23.6" cy="31.4" rx="9.6" ry="6.1" transform="rotate(20 23.6 31.4)" fill="url(#mataSpider)" stroke="#0d0f13" stroke-width="2.6"/>
    </defs>

    <use xlink:href="#bentukHelm" fill="url(#helmSpider)" stroke="#4d0609" stroke-width="2.6" stroke-linejoin="round"/>

    <g clip-path="url(#maskClip)" stroke="#5c0a10" stroke-width="1.25" opacity="0.72" stroke-linecap="round" fill="none">
        <path d="M36 17.4L14.2 11.2 M36 17.4L21.4 7.6 M36 17.4L36 4 M36 17.4L50.6 7.6 M36 17.4L57.8 11.2 M36 17.4L11.2 26.8 M36 17.4L60.8 26.8"/>
        <path d="M14.2 20.6c7.2 5.6 14.8 8.6 21.8 8.6s14.6-3 21.8-8.6"/>
        <path d="M12.6 30.8c7.8 6.2 15 9.1 23.4 9.1s15.6-2.9 23.4-9.1"/>
        <path d="M16.4 42.2c6.4 4.8 12.6 6.9 19.6 6.9s13.2-2.1 19.6-6.9"/>
        <path d="M23.8 51.6c4 2.3 8.1 3.4 12.2 3.4s8.2-1.1 12.2-3.4"/>
    </g>

    <path d="M20.6 12.2c5-3.5 10.8-5.3 15.4-5.3" fill="none" stroke="#ffffff" stroke-width="2" opacity="0.42" stroke-linecap="round"/>

    <g clip-path="url(#maskClip)">
        <use xlink:href="#mataKiri"/>
        <use xlink:href="#mataKiri" transform="translate(72 0) scale(-1 1)"/>
    </g>
</svg>
