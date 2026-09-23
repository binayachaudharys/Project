# Plan: Refresh-style public home

> Spec: `docs/superpowers/specs/2026-09-08-refresh-style-public-home-design.md`

## Files

- `config/salon.php` — contact + social + menu categories
- `.env` / `.env.example` — SALON_PHONE, ADDRESS, INSTAGRAM, FACEBOOK
- `app/Http/Controllers/Public/HomeController.php` — services + salon bag
- `resources/js/Layouts/PublicLayout.jsx` — nav, logo, Book Now, footer
- `resources/js/Pages/Home.jsx` — full marketing sections
- `public/images/*` — logo + flyers

## Tasks

1. Extend salon config + env
2. HomeController data
3. PublicLayout
4. Home page
5. Build assets + smoke check `/`
