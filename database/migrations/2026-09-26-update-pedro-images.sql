UPDATE campaigns
SET
    hero_image = 'assets/img/capa-rifa-pedro.webp',
    gallery_json = JSON_ARRAY(
        'assets/img/pedro-1.webp',
        'assets/img/pedro-2.webp',
        'assets/img/pedro-3.webp',
        'assets/img/pedro-familia.webp'
    )
WHERE slug = 'ajude-o-pedro';
