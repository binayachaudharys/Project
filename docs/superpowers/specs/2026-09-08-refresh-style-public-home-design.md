# Pretty Salon Nepal — Refresh-style public home

**Date:** 2026-09-08  
**Status:** Approved  
**Reference:** [Refresh Hair Studio](https://refreshhairstudiochicago.com/) layout · Pretty Salon Nepal brand  

## Goal

Rebuild public **Home + PublicLayout** to match Refresh’s marketing structure, using Pretty Salon Nepal branding, flyer package menus, and live bookable services from the DB.

## Scope

- In: `PublicLayout.jsx`, `Home.jsx`, `HomeController`, `config/salon.php` (+ `.env` keys), static images under `public/images/`
- Out: restyling `/services` & `/packages` catalog pages; stylist CMS; real photo gallery uploads

## Brand

| Field | Value |
|--------|--------|
| Display name | Pretty Salon Nepal |
| Logo | `public/images/logo.png` (from flyer) |
| Address | Shankhamul Pool, 3rd Floor (NIMB Building) |
| Phone / WhatsApp | +977-9822783805 |
| Instagram | https://www.instagram.com/prettysalonnepal/ |
| Facebook | https://www.facebook.com/people/Pretty-Salon-Nepal/61584002914099/ |
| Promo | Grand opening — 30% OFF first month |

## Visual

- Keep rose / blush / charcoal Tailwind tokens
- Refresh-like section rhythm: sticky header, full-bleed hero, notice strip, menu grids, testimonials, flyer gallery, rich footer
- Brand as hero-level signal; one primary CTA group (Book Now)

## Home sections

1. Promo notice strip (30% OFF)
2. Hero — logo/name, “A whole new you”, Book Now + WhatsApp
3. Welcome copy
4. Flyer package menus (static from flyers) — categorized price lists
5. Bookable online — active `Service` rows from DB
6. Quick links — Services, Packages, Book, Instagram
7. Testimonials — 3 placeholders
8. Gallery — flyer images
9. Footer — address, phone, hours, social, Book Online

## Data

- `HomeController` passes: `salon` config bag, `services` (active), `menuCategories` (static PHP array or config)
- Hours from `salon_open` / `salon_close`

## Success

- `/` looks like a salon marketing site (not a short hero only)
- Book Now → `/book` or `/login`
- Contact WhatsApp + social links work
- `npm run build` succeeds
