<?php

return [
    'index' => [
        [
            'key' => 'hero_title',
            'label' => 'Home - Titolo hero',
            'selector' => '.hero-title',
            'field' => 'text',
        ],
        [
            'key' => 'hero_subtitle',
            'label' => 'Home - Sottotitolo hero',
            'selector' => '.hero-subtitle',
            'field' => 'text',
        ],
        [
            'key' => 'hero_cta_primary_text',
            'label' => 'Home - Bottone hero principale (testo)',
            'selector' => '.hero-buttons .btn-primary',
            'field' => 'text',
        ],
        [
            'key' => 'hero_cta_primary_url',
            'label' => 'Home - Bottone hero principale (link)',
            'selector' => '.hero-buttons .btn-primary',
            'field' => 'url',
            'attribute' => 'href',
        ],
        [
            'key' => 'hero_cta_secondary_text',
            'label' => 'Home - Bottone hero secondario (testo)',
            'selector' => '.hero-buttons .btn-secondary',
            'field' => 'text',
        ],
        [
            'key' => 'hero_cta_secondary_url',
            'label' => 'Home - Bottone hero secondario (link)',
            'selector' => '.hero-buttons .btn-secondary',
            'field' => 'url',
            'attribute' => 'href',
        ],
        [
            'key' => 'advantages_title',
            'label' => 'Home - Titolo sezione vantaggi',
            'selector' => '.advantages .section-title',
            'field' => 'text',
        ],
        [
            'key' => 'advantages_subtitle',
            'label' => 'Home - Sottotitolo sezione vantaggi',
            'selector' => '.advantages .section-subtitle',
            'field' => 'text',
        ],
        [
            'key' => 'how_title',
            'label' => 'Home - Titolo Come funziona',
            'selector' => '.how-it-works .section-title',
            'field' => 'text',
        ],
        [
            'key' => 'how_subtitle',
            'label' => 'Home - Sottotitolo Come funziona',
            'selector' => '.how-it-works .section-subtitle',
            'field' => 'text',
        ],
        [
            'key' => 'faq_title',
            'label' => 'Home - Titolo FAQ',
            'selector' => '.faq .section-title',
            'field' => 'text',
        ],
        [
            'key' => 'cta_title',
            'label' => 'Home - Titolo CTA finale',
            'selector' => '.cta-box h2',
            'field' => 'text',
        ],
        [
            'key' => 'cta_subtitle',
            'label' => 'Home - Testo CTA finale',
            'selector' => '.cta-box p',
            'field' => 'text',
        ],
    ],
    'chi-siamo' => [
        [
            'key' => 'page_title',
            'label' => 'Chi siamo - Titolo pagina',
            'selector' => '.page-hero h1',
            'field' => 'text',
        ],
        [
            'key' => 'page_subtitle',
            'label' => 'Chi siamo - Sottotitolo pagina',
            'selector' => '.page-hero p',
            'field' => 'text',
        ],
        [
            'key' => 'about_title',
            'label' => 'Chi siamo - Titolo sezione principale',
            'selector' => '.about-text h2',
            'field' => 'text',
        ],
        [
            'key' => 'about_intro',
            'label' => 'Chi siamo - Testo introduttivo',
            'selector' => '.about-text .lead',
            'field' => 'text',
        ],
        [
            'key' => 'mission_title',
            'label' => 'Chi siamo - Titolo missione',
            'selector' => '.mission-box h3',
            'field' => 'text',
        ],
        [
            'key' => 'mission_text',
            'label' => 'Chi siamo - Testo missione',
            'selector' => '.mission-box p',
            'field' => 'text',
        ],
    ],
    'orari-tariffe' => [
        [
            'key' => 'page_title',
            'label' => 'Orari e tariffe - Titolo pagina',
            'selector' => '.page-hero h1',
            'field' => 'text',
        ],
        [
            'key' => 'page_subtitle',
            'label' => 'Orari e tariffe - Sottotitolo pagina',
            'selector' => '.page-hero p',
            'field' => 'text',
        ],
        [
            'key' => 'schedule_title',
            'label' => 'Orari e tariffe - Titolo sezione orari',
            'selector' => '.schedule-section .section-title',
            'field' => 'text',
        ],
        [
            'key' => 'prices_title',
            'label' => 'Orari e tariffe - Titolo prezzi',
            'selector' => '.pricing-section .section-title',
            'field' => 'text',
        ],
    ],
    'galleria' => [
        [
            'key' => 'page_title',
            'label' => 'Galleria - Titolo pagina',
            'selector' => '.page-hero h1',
            'field' => 'text',
        ],
        [
            'key' => 'page_subtitle',
            'label' => 'Galleria - Sottotitolo pagina',
            'selector' => '.page-hero p',
            'field' => 'text',
        ],
        [
            'key' => 'gallery_intro',
            'label' => 'Galleria - Testo introduttivo',
            'selector' => '.gallery-intro',
            'field' => 'text',
        ],
    ],
    'moduli' => [
        [
            'key' => 'page_title',
            'label' => 'Moduli - Titolo pagina',
            'selector' => '.page-hero h1',
            'field' => 'text',
        ],
        [
            'key' => 'page_subtitle',
            'label' => 'Moduli - Sottotitolo pagina',
            'selector' => '.page-hero p',
            'field' => 'text',
        ],
        [
            'key' => 'docs_title',
            'label' => 'Moduli - Titolo sezione documenti',
            'selector' => '.documents-section .section-title',
            'field' => 'text',
        ],
        [
            'key' => 'docs_subtitle',
            'label' => 'Moduli - Sottotitolo sezione documenti',
            'selector' => '.documents-section .section-subtitle',
            'field' => 'text',
        ],
    ],
    'pacchetti' => [
        [
            'key' => 'page_title',
            'label' => 'Pacchetti - Titolo pagina',
            'selector' => '.page-hero h1',
            'field' => 'text',
        ],
        [
            'key' => 'page_subtitle',
            'label' => 'Pacchetti - Sottotitolo pagina',
            'selector' => '.page-hero p',
            'field' => 'text',
        ],
        [
            'key' => 'packages_title',
            'label' => 'Pacchetti - Titolo sezione pacchetti',
            'selector' => '#packagesSection .section-title',
            'field' => 'text',
        ],
        [
            'key' => 'packages_subtitle',
            'label' => 'Pacchetti - Sottotitolo sezione pacchetti',
            'selector' => '#packagesSection .section-subtitle',
            'field' => 'text',
        ],
        [
            'key' => 'security_title',
            'label' => 'Pacchetti - Titolo sicurezza',
            'selector' => '.security-section .section-title',
            'field' => 'text',
        ],
        [
            'key' => 'faq_title',
            'label' => 'Pacchetti - Titolo FAQ',
            'selector' => '.faq .section-title',
            'field' => 'text',
        ],
    ],
    'contatti' => [
        [
            'key' => 'page_title',
            'label' => 'Contatti - Titolo pagina',
            'selector' => '.page-hero h1',
            'field' => 'text',
        ],
        [
            'key' => 'page_subtitle',
            'label' => 'Contatti - Sottotitolo pagina',
            'selector' => '.page-hero p',
            'field' => 'text',
        ],
        [
            'key' => 'contact_form_title',
            'label' => 'Contatti - Titolo form',
            'selector' => '.contact-form-container h3',
            'field' => 'text',
        ],
        [
            'key' => 'contact_info_title',
            'label' => 'Contatti - Titolo info',
            'selector' => '.contact-info h3',
            'field' => 'text',
        ],
    ],
];

