<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#111111">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/logo.png') }}">
    <title>@yield('title', optional($generalsetting)->name ?? 'Shop')</title>
    <link rel="shortcut icon" href="{{ asset(optional($generalsetting)->favicon) }}">
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/owl.theme.default.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/backEnd/assets/css/toastr.min.css') }}">
    {{-- Current feature pages keep their structure; this stylesheet supplies their base components. --}}
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/storefront.css') }}?v=5">
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/avoronno.css') }}?v=1">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @stack('seo')
    <style>

        /* ========================================
           DESIGN TOKENS
           ======================================== */
        :root {
            --ink: #111111;
            --muted: #6b7280;
            --line: #e5e7eb;
            --paper: #ffffff;
            --soft: #f9fafb;
            --accent: #111111;
            --success: #059669;
            --danger: #dc2626;
            --radius: 8px;
            --radius-lg: 12px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06);
            --shadow-md: 0 4px 16px rgba(0,0,0,.08);
            --shadow-lg: 0 12px 40px rgba(0,0,0,.12);
            --transition: .25s cubic-bezier(.4,0,.2,1);
            --header-h: 66px;
            --announce-h: 36px;
            --nav-h: 44px;
            --c-primary: #111111;
            --c-primary-600: #000000;
            --c-primary-50: #f4f4f4;
            --c-accent: #111111;
            --c-accent-600: #000000;
            --c-accent-50: #f4f4f4;
            --quick-order-color: {{ optional($generalsetting)->secodery_color ?? '#e02b20' }};
            --c-text: #1a1a1a;
            --c-muted: #6b7280;
            --c-faint: #9ca3af;
            --c-line: #e5e7eb;
            --c-bg: #ffffff;
            --r-sm: 8px;
            --r: 10px;
            --r-lg: 14px;
            --sh-sm: 0 1px 3px rgba(0,0,0,.06);
            --sh-md: 0 4px 16px rgba(0,0,0,.08);
        }

        /* ========================================
           BASE
           ======================================== */
        *{box-sizing:border-box;margin:0;padding:0}
        html{scroll-behavior:smooth;-webkit-tap-highlight-color:transparent}
        body{color:var(--ink);background:var(--paper);font-family:Manrope,"Hind Siliguri",sans-serif;line-height:1.5;overflow-x:hidden}
        a{text-decoration:none;color:inherit;transition:color var(--transition)}
        img{max-width:100%;display:block}
        button{cursor:pointer;font-family:inherit}
        input,select,textarea{font-family:inherit}
        ul,ol{list-style:none}

        /* ========================================
           UTILITY
           ======================================== */
        .fashion-container{max-width:1240px;margin:0 auto;padding:0 20px}

        /* ========================================
           ANNOUNCEMENT BAR
           ======================================== */
        .announce-bar{
            background:var(--ink);
            color:#fff;
            height:var(--announce-h);
            display:flex;
            align-items:center;
            position:relative;
            overflow:hidden;
            z-index:110;
        }
        .announce-bar.hidden{display:none}
        .announce-inner{
            display:flex;
            align-items:center;
            justify-content:center;
            width:100%;
            gap:12px;
        }
        .announce-slider{
            display:flex;
            animation:announceSlide 20s linear infinite;
            white-space:nowrap;
        }
        .announce-slider span{
            padding:0 40px;
            font-size:12px;
            font-weight:600;
            letter-spacing:.03em;
        }
        .announce-slider span i{margin-right:4px;opacity:.85}
        @keyframes announceSlide{
            0%{transform:translateX(0)}
            100%{transform:translateX(-50%)}
        }
        .announce-close{
            position:absolute;
            right:16px;
            top:50%;
            transform:translateY(-50%);
            background:none;
            border:none;
            color:rgba(255,255,255,.6);
            font-size:18px;
            line-height:1;
            padding:2px 6px;
            transition:color var(--transition);
            z-index:2;
        }
        .announce-close:hover{color:#fff}

        /* ========================================
           HEADER
           ======================================== */
        .fashion-top{
            background:var(--paper);
            border-bottom:1px solid var(--line);
            position:sticky;
            top:0;
            z-index:100;
            transition:box-shadow var(--transition);
        }
        .fashion-top.is-scrolled{
            box-shadow:0 2px 20px rgba(0,0,0,.08);
        }
        .fashion-header{
            height:var(--header-h);
            display:grid;
            grid-template-columns:160px minmax(240px,1fr) auto;
            align-items:center;
            gap:20px;
        }
        .fashion-logo img{
            max-width:140px;
            max-height:40px;
            object-fit:contain;
            transition:transform var(--transition);
        }
        .fashion-logo:hover img{transform:scale(1.03)}

        /* Search */
        .fashion-search{
            display:flex;
            border:1.5px solid var(--line);
            border-radius:100px;
            overflow:hidden;
            transition:border-color var(--transition),box-shadow var(--transition);
        }
        .fashion-search:focus-within{
            border-color:var(--accent);
            box-shadow:0 0 0 3px rgba(0,0,0,.06);
        }
        .fashion-search input{
            height:40px;
            border:0;
            outline:0;
            flex:1;
            padding:0 18px;
            font-size:13px;
            background:transparent;
            min-width:0;
        }
        .fashion-search input::placeholder{color:#aaa}
        .fashion-search button{
            border:0;
            background:var(--accent);
            color:#fff;
            width:44px;
            font-size:15px;
            display:grid;
            place-items:center;
            transition:opacity var(--transition);
        }
        .fashion-search button:hover{opacity:.85}

        /* Actions */
        .fashion-actions{
            display:flex;
            align-items:center;
            gap:6px;
        }
        .action-item{
            display:flex;
            align-items:center;
            gap:6px;
            padding:8px 12px;
            border-radius:var(--radius);
            font-size:12px;
            font-weight:700;
            white-space:nowrap;
            transition:background var(--transition);
            position:relative;
        }
        .action-item:hover{background:var(--soft)}
        .action-item i{font-size:17px;color:var(--ink)}
        .action-label{color:var(--ink)}
        .cart-count{
            position:absolute;
            top:2px;
            right:4px;
            display:grid;
            place-items:center;
            background:var(--accent);
            color:#fff;
            border-radius:50%;
            font-size:9px;
            font-weight:800;
            width:17px;
            height:17px;
            line-height:17px;
            text-align:center;
        }
        .action-phone{display:none}

        /* ========================================
           NAVIGATION
           ======================================== */
        .fashion-nav-wrap{
            border-bottom:1px solid var(--line);
            background:var(--paper);
            position:sticky;
            top:var(--header-h);
            z-index:99;
            transition:box-shadow var(--transition);
        }
        .fashion-top.is-scrolled ~ .fashion-nav-wrap{
            box-shadow:0 2px 12px rgba(0,0,0,.04);
        }
        .fashion-nav{
            height:var(--nav-h);
            display:flex;
            align-items:center;
            gap:0;
        }
        .fashion-nav>a,
        .fashion-nav .nav-drop>a{
            display:flex;
            align-items:center;
            padding:0 18px;
            font-size:12px;
            font-weight:700;
            letter-spacing:.04em;
            text-transform:uppercase;
            border-left:1px solid var(--line);
            transition:background var(--transition),color var(--transition);
            position:relative;
        }
        .fashion-nav>a:first-child,
        .fashion-nav .nav-drop:first-child>a{border-left:none}
        .fashion-nav>a:hover,
        .fashion-nav .nav-drop>a:hover{
            background:var(--soft);
            color:var(--accent);
        }
        .fashion-nav .nav-drop>a i{margin-left:6px;font-size:10px;opacity:.6;transition:transform var(--transition)}
        .fashion-nav .nav-drop:hover>a i{transform:rotate(180deg)}

        /* Mega Menu */
        .nav-drop{position:relative}
        .nav-drop:hover .mega-menu{display:grid;opacity:1;transform:translateY(0);pointer-events:all}
        .mega-menu{
            display:grid;
            grid-template-columns:repeat(3,minmax(140px,1fr)) 180px;
            gap:0;
            position:absolute;
            z-index:200;
            top:100%;
            left:0;
            min-width:620px;
            background:var(--paper);
            border:1px solid var(--line);
            border-top:2px solid var(--accent);
            border-radius:0 0 var(--radius-lg) var(--radius-lg);
            box-shadow:var(--shadow-lg);
            opacity:0;
            transform:translateY(6px);
            transition:opacity .2s ease,transform .2s ease;
            pointer-events:none;
            overflow:hidden;
        }
        .mega-col{
            padding:18px 20px;
            border-right:1px solid var(--line);
        }
        .mega-col:last-of-type{border-right:none}
        .mega-col strong{
            font-size:12px;
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:.04em;
            color:var(--ink);
            padding-bottom:8px;
            margin-bottom:6px;
            display:block;
            border-bottom:1px solid var(--line);
        }
        .mega-col a{
            display:block;
            color:var(--muted);
            font-size:12px;
            padding:5px 0;
            transition:color var(--transition),padding-left var(--transition);
        }
        .mega-col a:hover{color:var(--accent);padding-left:4px}
        .mega-promo{
            background:var(--soft);
            display:flex;
            flex-direction:column;
            justify-content:center;
            padding:18px;
        }
        .mega-promo a{
            display:flex;
            flex-direction:column;
            align-items:center;
            text-align:center;
            gap:8px;
        }
        .mega-promo img{
            width:100%;
            height:90px;
            object-fit:cover;
            border-radius:var(--radius);
        }
        .mega-promo span{
            font-size:11px;
            color:var(--muted);
            font-weight:600;
            text-transform:uppercase;
            letter-spacing:.06em;
        }
        .mega-promo strong{
            font-size:14px;
            color:var(--ink);
        }

        /* ========================================
           BREADCRUMB
           ======================================== */
        .breadcrumb-bar{
            background:var(--soft);
            border-bottom:1px solid var(--line);
            padding:10px 0;
            font-size:12px;
            color:var(--muted);
        }
        .breadcrumb-bar a{color:var(--muted);transition:color var(--transition)}
        .breadcrumb-bar a:hover{color:var(--accent)}

        /* ========================================
           FOOTER
           ======================================== */
        .fashion-footer{
            background:#111;
            color:#ccc;
            margin-top:60px;
        }

        /* Newsletter */
        .footer-newsletter{
            background:var(--accent);
            padding:32px 0;
            margin-bottom:0;
        }
        .newsletter-inner{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:24px;
        }
        .newsletter-text h3{
            font-size:18px;
            font-weight:800;
            color:#fff;
            margin-bottom:2px;
        }
        .newsletter-text p{
            font-size:13px;
            color:rgba(255,255,255,.75);
            margin:0;
        }
        .newsletter-form{
            display:flex;
            gap:0;
            max-width:380px;
            width:100%;
        }
        .newsletter-form input{
            flex:1;
            height:44px;
            border:0;
            outline:0;
            padding:0 16px;
            font-size:13px;
            border-radius:var(--radius) 0 0 var(--radius);
            min-width:0;
        }
        .newsletter-form button{
            height:44px;
            padding:0 22px;
            background:#fff;
            color:var(--accent);
            border:0;
            font-weight:800;
            font-size:13px;
            border-radius:0 var(--radius) var(--radius) 0;
            white-space:nowrap;
            transition:opacity var(--transition);
        }
        .newsletter-form button:hover{opacity:.85}

        /* Footer Grid */
        .footer-body{padding:44px 0 32px}
        .footer-grid{
            display:grid;
            grid-template-columns:2fr 1fr 1fr 1fr;
            gap:36px;
        }
        .footer-brand img{
            max-width:130px;
            max-height:36px;
            object-fit:contain;
            filter:brightness(0) invert(1);
            margin-bottom:14px;
        }
        .footer-brand p{
            font-size:12px;
            color:#888;
            line-height:1.7;
            margin-bottom:14px;
        }
        .footer-social{
            display:flex;
            gap:10px;
        }
        .footer-social a{
            width:34px;
            height:34px;
            display:grid;
            place-items:center;
            border-radius:50%;
            background:rgba(255,255,255,.08);
            color:#aaa;
            font-size:14px;
            transition:background var(--transition),color var(--transition);
        }
        .footer-social a:hover{background:#fff;color:#111}
        .footer-col h6{
            font-size:12px;
            font-weight:800;
            color:#fff;
            text-transform:uppercase;
            letter-spacing:.06em;
            margin-bottom:16px;
            padding-bottom:10px;
            border-bottom:1px solid rgba(255,255,255,.1);
        }
        .footer-col a{
            display:block;
            font-size:12px;
            color:#888;
            padding:5px 0;
            transition:color var(--transition),padding-left var(--transition);
        }
        .footer-col a:hover{color:#fff;padding-left:3px}
        .footer-contact-item{
            display:flex;
            align-items:flex-start;
            gap:10px;
            margin-bottom:10px;
        }
        .footer-contact-item i{
            margin-top:3px;
            font-size:13px;
            color:#666;
            width:16px;
            text-align:center;
        }
        .footer-contact-item span{font-size:12px;color:#888}

        /* Footer Bottom */
        .footer-bottom{
            border-top:1px solid rgba(255,255,255,.08);
            padding:18px 0;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            flex-wrap:wrap;
        }
        .footer-bottom p{
            font-size:11px;
            color:#666;
            margin:0;
        }
        .footer-payments{
            display:flex;
            align-items:center;
            gap:8px;
        }
        .footer-payments span{
            font-size:10px;
            color:#666;
            text-transform:uppercase;
            letter-spacing:.04em;
        }
        .footer-payments i{
            font-size:22px;
            color:#555;
            transition:color var(--transition);
        }
        .footer-payments i:hover{color:#fff}

        /* ========================================
           MOBILE ELEMENTS
           ======================================== */
        .mobile-head{display:none}
        .mobile-search{display:none}

        /* Mobile Drawer */
        .mobile-overlay{
            display:none;
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.5);
            z-index:600;
            opacity:0;
            transition:opacity .3s ease;
        }
        .mobile-overlay.active{display:block;opacity:1}
        .mobile-drawer{
            position:fixed;
            top:0;
            left:0;
            width:300px;
            max-width:85vw;
            height:100%;
            background:var(--paper);
            z-index:601;
            transform:translateX(-100%);
            transition:transform .3s cubic-bezier(.4,0,.2,1);
            overflow-y:auto;
            overscroll-behavior:contain;
        }
        .mobile-drawer.active{transform:translateX(0)}
        .drawer-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:16px 20px;
            border-bottom:1px solid var(--line);
            background:var(--soft);
            position:sticky;
            top:0;
            z-index:2;
        }
        .drawer-header span{font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
        .drawer-close{
            background:none;
            border:none;
            font-size:22px;
            color:var(--muted);
            padding:0;
            line-height:1;
            transition:color var(--transition);
        }
        .drawer-close:hover{color:var(--ink)}
        .drawer-body{padding:8px 0}
        .drawer-user{
            display:flex;
            align-items:center;
            gap:12px;
            padding:14px 20px;
            border-bottom:1px solid var(--line);
        }
        .drawer-user-icon{
            width:40px;
            height:40px;
            display:grid;
            place-items:center;
            border-radius:50%;
            background:var(--soft);
            font-size:16px;
            color:var(--muted);
        }
        .drawer-user a{font-size:13px;font-weight:700}
        .drawer-user small{font-size:11px;color:var(--muted);display:block}
        .drawer-item{border-bottom:1px solid #f0f0f0}
        .drawer-link{
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:13px 20px;
            font-size:13px;
            font-weight:700;
            transition:background var(--transition);
        }
        .drawer-link:hover{background:var(--soft)}
        .drawer-link i{font-size:11px;color:var(--muted);transition:transform var(--transition)}
        .drawer-link.expanded i{transform:rotate(180deg)}
        .drawer-sub{
            display:none;
            padding:0 20px 10px 20px;
        }
        .drawer-sub.open{display:block}
        .drawer-sub a{
            display:block;
            font-size:12px;
            color:var(--muted);
            padding:7px 0 7px 12px;
            border-left:2px solid var(--line);
            margin-left:6px;
            transition:color var(--transition),border-color var(--transition);
        }
        .drawer-sub a:hover{color:var(--accent);border-color:var(--accent)}
        .drawer-links{padding:12px 20px}
        .drawer-links a{
            display:flex;
            align-items:center;
            gap:10px;
            font-size:12px;
            color:var(--muted);
            padding:8px 0;
            transition:color var(--transition);
        }
        .drawer-links a i{width:16px;text-align:center;font-size:13px}
        .drawer-links a:hover{color:var(--accent)}

        /* Mobile Bottom Nav */
        .mobile-nav{display:none}

        /* ========================================
           BACK TO TOP
           ======================================== */
        .back-to-top{
            position:fixed;
            bottom:80px;
            right:20px;
            width:42px;
            height:42px;
            display:grid;
            place-items:center;
            background:var(--accent);
            color:#fff;
            border:0;
            border-radius:50%;
            font-size:16px;
            z-index:90;
            opacity:0;
            transform:translateY(12px);
            pointer-events:none;
            transition:opacity .25s ease,transform .25s ease,background var(--transition);
            box-shadow:0 4px 14px rgba(0,0,0,.2);
        }
        .back-to-top.visible{opacity:1;transform:translateY(0);pointer-events:all}
        .back-to-top:hover{background:#333}

        /* ========================================
           SEARCH RESULTS (dropdown)
           ======================================== */
        .search-result{
            position:absolute;
            z-index:200;
            max-width:520px;
            width:calc(100% + 2px);
            top:calc(100% + 4px);
            background:var(--paper);
            box-shadow:var(--shadow-lg);
            border-radius:var(--radius);
            border:1px solid var(--line);
            display:none;
        }
        .search-result.has-results{display:block}
        .search_product ul{list-style:none;padding:0;margin:0}
        .search_product li{border-bottom:1px solid #f5f5f5}
        .search_product li:last-child{border-bottom:none}
        .search_product a{
            display:flex;
            gap:12px;
            padding:10px 14px;
            transition:background var(--transition);
        }
        .search_product a:hover{background:var(--soft)}
        .search_img img{width:48px;height:48px;object-fit:cover;border-radius:6px}
        .search_content .name{margin:0;font-size:12px;font-weight:700;line-height:1.3}
        .search_content .price{margin:3px 0 0;font-size:12px;color:var(--accent);font-weight:700}

        /* ========================================
           RESPONSIVE — TABLET
           ======================================== */
        @media(max-width:1024px){
            .fashion-header{grid-template-columns:140px minmax(180px,1fr) auto;gap:14px}
            .mega-menu{grid-template-columns:repeat(3,minmax(120px,1fr)) 160px;min-width:540px}
            .newsletter-inner{flex-direction:column;text-align:center}
            .newsletter-form{max-width:100%}
        }

        /* ========================================
           RESPONSIVE — MOBILE
           ======================================== */

        @media(max-width:767px){
            :root{--header-h:56px}
            body{padding-bottom:66px}

            /* Hide desktop elements */
            .fashion-header,.fashion-nav-wrap,.action-phone{display:none}
            .back-to-top{bottom:80px;right:14px;width:38px;height:38px;font-size:14px}

            /* Announcement */
            .announce-bar{height:32px}
            .announce-slider span{font-size:11px;padding:0 24px}

            /* Mobile Header */
            .mobile-head{
                display:grid;
                grid-template-columns:36px 1fr 40px;
                align-items:center;
                gap:8px;
                height:var(--header-h);
                padding:0 16px;
                background:var(--paper);
                border-bottom:1px solid var(--line);
                position:sticky;
                top:0;
                z-index:100;
            }
            .mobile-head .hamburger{
                display:grid;
                place-items:center;
                width:36px;
                height:36px;
                background:none;
                border:none;
                font-size:20px;
                color:var(--ink);
                border-radius:var(--radius);
                transition:background var(--transition);
            }
            .mobile-head .hamburger:hover{background:var(--soft)}
            .mobile-head img{max-height:32px;max-width:120px;object-fit:contain;margin:0 auto}
            .mobile-head .mobile-cart{
                display:grid;
                place-items:center;
                width:40px;
                height:40px;
                position:relative;
                font-size:18px;
                color:var(--ink);
            }

            /* Mobile Search */
            .mobile-search{
                display:flex;
                border:1px solid var(--line);
                border-radius:100px;
                margin:8px 16px 0;
                overflow:hidden;
            }
            .mobile-search input{border:0;outline:0;flex:1;height:38px;padding:0 14px;font-size:12px;background:transparent}
            .mobile-search input::placeholder{color:#bbb}
            .mobile-search button{border:0;background:var(--accent);color:#fff;width:40px;font-size:14px}

            /* Mobile Bottom Nav */
            .mobile-nav{
                display:flex;
                position:fixed;
                bottom:0;left:0;right:0;
                height:66px;
                background:var(--paper);
                border-top:1px solid var(--line);
                justify-content:space-around;
                align-items:center;
                z-index:500;
                box-shadow:0 -2px 12px rgba(0,0,0,.06);
            }
            .mobile-nav a{
                text-align:center;
                font-size:9px;
                font-weight:700;
                color:var(--muted);
                transition:color var(--transition);
                display:flex;
                flex-direction:column;
                align-items:center;
                gap:3px;
                position:relative;
            }
            .mobile-nav a.active,
            .mobile-nav a:hover{color:var(--accent)}
            .mobile-nav i{font-size:18px}
            .mobile-nav .cart-badge{
                position:absolute;
                top:-2px;
                right:-6px;
                background:var(--accent);
                color:#fff;
                font-size:8px;
                font-weight:800;
                width:15px;
                height:15px;
                border-radius:50%;
                display:grid;
                place-items:center;
            }

            /* Breadcrumb */
            .breadcrumb-bar{padding:9px 0;font-size:10px}

            /* Footer */
            .fashion-footer{margin-top:40px}
            .footer-newsletter{padding:24px 0}
            .newsletter-text h3{font-size:15px}
            .newsletter-text p{font-size:11px}
            .newsletter-form input{height:40px;font-size:12px}
            .newsletter-form button{height:40px;padding:0 16px;font-size:12px}
            .footer-body{padding:30px 0 20px}
            .footer-grid{grid-template-columns:1fr 1fr;gap:24px}
            .footer-brand{grid-column:1/-1}
            .footer-bottom{flex-direction:column;text-align:center;gap:10px;padding:14px 0}
            .footer-payments{flex-wrap:wrap;justify-content:center}

        }

        /* ========================================
           RESPONSIVE — SMALL MOBILE
           ======================================== */
        @media(max-width:380px){
            .fashion-container{padding:0 14px}
            .footer-grid{grid-template-columns:1fr;gap:20px}
            .mobile-drawer{width:280px}
        }
    </style>
    @stack('css')

    {{-- Existing analytics integrations are intentionally preserved. --}}
    @php
        $dlPageType = Request::is('/') ? 'home'
            : (Request::is('product/*') ? 'product_detail'
            : (Request::is('category/*') || Request::is('subcategory/*') || Request::is('products/*') ? 'category'
            : (Request::is('cart') ? 'cart' : (Request::is('checkout') ? 'checkout' : 'other'))));
    @endphp
    <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({event:'site_page_data',page_type:@json($dlPageType),page_url:@json(url()->current()),currency:'BDT',site_name:@json(optional($generalsetting)->name ?? '')});
    </script>
    @foreach($gtm_code ?? [] as $gtm)
        @php $gtmContainerId = preg_match('/^GTM-/i', trim($gtm->code)) ? trim($gtm->code) : 'GTM-'.trim($gtm->code); @endphp
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtmContainerId }}');</script>
    @endforeach
    @if(isset($pixels) && $pixels->count())
        <script>
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
            @foreach($pixels as $pixel) fbq('init', '{{ $pixel->code }}'); @endforeach
            fbq('track','PageView');
        </script>
    @endif
</head>
<body>
    @foreach($gtm_code ?? [] as $gtm)
        @php $gtmNoscriptId = preg_match('/^GTM-/i', trim($gtm->code)) ? trim($gtm->code) : 'GTM-'.trim($gtm->code); @endphp
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmNoscriptId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endforeach

    {{-- ===== ANNOUNCEMENT BAR ===== --}}
    <div class="announce-bar" id="announceBar">
        <div class="announce-inner">
            <div class="announce-slider">
                @if(($generalsetting->news_ticker_enabled ?? 0) && !empty($generalsetting->top_headline))
                    <span><i class="fa-solid fa-bullhorn"></i> {{ $generalsetting->top_headline }}</span>
                    <span><i class="fa-solid fa-bullhorn"></i> {{ $generalsetting->top_headline }}</span>
                @else
                    <span><i class="fa-solid fa-truck-fast"></i> সারা বাংলাদেশে দ্রুত ডেলিভারি</span>
                    <span><i class="fa fa-money-bill-wave"></i> ক্যাশ অন ডেলিভারি — পণ্য হাতে পেয়ে মূল্য দিন</span>
                    <span><i class="fa fa-shield-halved"></i> নিরাপদ ও বিশ্বস্ত কেনাকাটা</span>
                    <span><i class="fa-solid fa-truck-fast"></i> সারা বাংলাদেশে দ্রুত ডেলিভারি</span>
                    <span><i class="fa fa-money-bill-wave"></i> ক্যাশ অন ডেলিভারি — পণ্য হাতে পেয়ে মূল্য দিন</span>
                    <span><i class="fa fa-shield-halved"></i> নিরাপদ ও বিশ্বস্ত কেনাকাটা</span>
                @endif
            </div>
            <button class="announce-close" id="announceClose" aria-label="Close">&times;</button>
        </div>
    </div>

    {{-- ===== HEADER ===== --}}
    <header class="fashion-top" id="siteHeader">
        <div class="fashion-container">
            <div class="fashion-header">
                <a class="fashion-logo" href="{{ route('home') }}">
                    <img src="{{ asset(optional($generalsetting)->white_logo) }}" alt="{{ optional($generalsetting)->name }}">
                </a>
                <div class="fashion-search" style="position:relative">
                    <form action="{{ route('search') }}" style="display:flex;flex:1">
                        <input class="desktop-search" name="keyword" autocomplete="off" placeholder="Search for products...">
                        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                    <div class="search-result"></div>
                </div>
                <div class="fashion-actions">
                    <a href="tel:{{ optional($contact)->hotline }}" class="action-item action-phone">
                        <i class="fa fa-phone"></i>
                        <span class="action-label">{{ optional($contact)->hotline }}</span>
                    </a>
                    <a href="{{ Auth::guard('customer')->check() ? route('customer.account') : route('customer.login') }}" class="action-item">
                        <i class="fa-regular fa-user"></i>
                        <span class="action-label">{{ Auth::guard('customer')->check() ? 'Account' : 'Login' }}</span>
                    </a>
                    <a href="{{ route('customer.checkout') }}" class="action-item" data-open-cart>
                        <i class="fa-solid fa-bag-shopping"></i>
                        <span class="action-label">Cart</span>
                        <span class="cart-count js-cart-count">{{ Cart::instance('shopping')->count() }}</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Mobile Header --}}
        <div class="mobile-head">
            <button class="hamburger" id="drawerOpen" aria-label="Open menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a href="{{ route('home') }}">
                <img src="{{ asset(optional($generalsetting)->white_logo) }}" alt="logo">
            </a>
            <a class="mobile-cart" href="{{ route('customer.checkout') }}">
                <i class="fa-solid fa-bag-shopping"></i>
                <span class="cart-count js-cart-count">{{ Cart::instance('shopping')->count() }}</span>
            </a>
        </div>
        <div class="mobile-search">
            <input class="mobile-search-input" autocomplete="off" placeholder="Search products..." name="keyword">
            <button id="mobileSearchBtn"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
    </header>

    {{-- ===== NAVIGATION ===== --}}
    <nav class="fashion-nav-wrap">
        <div class="fashion-container">
            <div class="fashion-nav">
                <a href="{{ route('home') }}"><i class="fa fa-house"></i></a>
                @foreach(($menucategories ?? collect())->take(6) as $category)
                <div class="nav-drop">
                    <a href="{{ route('category',$category->slug) }}">
                        {{ strtoupper($category->name) }}
                        @if($category->subcategories->isNotEmpty())
                            <i class="fa-solid fa-chevron-down"></i>
                        @endif
                    </a>
                    @if($category->subcategories->isNotEmpty())
                    <div class="mega-menu">
                        @foreach($category->subcategories->take(6) as $sub)
                        <div class="mega-col">
                            <strong>{{ $sub->subcategoryName }}</strong>
                            @foreach($sub->childcategories->take(8) as $child)
                            <a href="{{ route('products',$child->slug) }}">{{ $child->childcategoryName }}</a>
                            @endforeach
                        </div>
                        @endforeach
                        <div class="mega-promo">
                            <a href="{{ route('category', $category->slug) }}">
                                @if($category->image)
                                <img src="{{ asset($category->image) }}" alt="{{ $category->name }}">
                                @else
                                <div style="width:100%;height:90px;background:var(--line);border-radius:var(--radius);display:grid;place-items:center;color:var(--muted);font-size:12px"><i class="fa fa-image" style="font-size:24px;opacity:.4"></i></div>
                                @endif
                                <span>Shop All</span>
                                <strong>{{ $category->name }}</strong>
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </nav>

    {{-- ===== MOBILE DRAWER ===== --}}
    <div class="mobile-overlay" id="mobileOverlay"></div>
    <div class="mobile-drawer" id="mobileDrawer">
        <div class="drawer-header">
            <span>Menu</span>
            <button class="drawer-close" id="drawerClose" aria-label="Close menu">&times;</button>
        </div>
        <div class="drawer-user">
            <div class="drawer-user-icon"><i class="fa-regular fa-user"></i></div>
            <div>
                <a href="{{ Auth::guard('customer')->check() ? route('customer.account') : route('customer.login') }}">
                    {{ Auth::guard('customer')->check() ? 'My Account' : 'Login / Sign Up' }}
                </a>
                <small>{{ Auth::guard('customer')->check() ? 'Manage your orders' : 'For exclusive deals' }}</small>
            </div>
        </div>
        <div class="drawer-body">
            @foreach(($menucategories ?? collect()) as $category)
            <div class="drawer-item">
                <a href="{{ route('category', $category->slug) }}" class="drawer-link">
                    {{ $category->name }}
                    @if($category->subcategories->isNotEmpty())
                        <i class="fa-solid fa-chevron-down"></i>
                    @endif
                </a>
                @if($category->subcategories->isNotEmpty())
                <div class="drawer-sub">
                    @foreach($category->subcategories as $sub)
                        <a href="{{ route('subcategory', $sub->slug) }}">{{ $sub->subcategoryName }}</a>
                        @foreach($sub->childcategories as $child)
                            <a href="{{ route('products', $child->slug) }}" style="margin-left:16px;font-size:11px;color:#999">{{ $child->childcategoryName }}</a>
                        @endforeach
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
        <div class="drawer-links">
            <a href="{{ route('hotdeals') }}"><i class="fa-solid fa-tags"></i> Offers & Deals</a>
            <a href="{{ route('customer.order_track') }}"><i class="fa-solid fa-truck"></i> Track Order</a>
            <a href="{{ route('contact') }}"><i class="fa-solid fa-headset"></i> Contact Us</a>
            <a href="tel:{{ optional($contact)->hotline }}"><i class="fa-solid fa-phone"></i> {{ optional($contact)->hotline }}</a>
        </div>
    </div>


    {{-- ===== MAIN CONTENT ===== --}}
    <main>@yield('content')</main>

    {{-- ===== FOOTER ===== --}}
    <footer class="fashion-footer">
        {{-- Newsletter --}}
        <div class="footer-newsletter">
            <div class="fashion-container">
                <div class="newsletter-inner">
                    <div class="newsletter-text">
                        <h3>Get offers straight to your inbox</h3>
                        <p>Subscribe for exclusive deals, new arrivals & updates.</p>
                    </div>
                    <form class="newsletter-form" action="{{ route('frontend.newsletter.subscribe') }}" method="POST">
                        @csrf
                        <input type="email" name="email" placeholder="Enter your email address" required>
                        <button type="submit">Subscribe</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Footer Body --}}
        <div class="footer-body">
            <div class="fashion-container">
                <div class="footer-grid">
                    {{-- Brand Column --}}
                    <div class="footer-brand">
                        <img src="{{ asset(optional($generalsetting)->white_logo) }}" alt="{{ optional($generalsetting)->name }}">
                        <p>{{ optional($contact)->address }}</p>
                        <div class="footer-contact-item">
                            <i class="fa-solid fa-phone"></i>
                            <span>{{ optional($contact)->hotline }}</span>
                        </div>
                        <div class="footer-contact-item">
                            <i class="fa-solid fa-envelope"></i>
                            <span>{{ optional($contact)->email }}</span>
                        </div>
                        <div class="footer-social" style="margin-top:14px">
                            @foreach($socialicons ?? [] as $social)
                                <a href="{{ $social->link }}" target="_blank" rel="noopener" aria-label="Social media"><i class="{{ $social->icon }}"></i></a>
                            @endforeach
                        </div>
                    </div>
                    {{-- Shop Column --}}
                    <div class="footer-col">
                        <h6>Shop</h6>
                        <a href="{{ route('shop') }}">All Products</a>
                        <a href="{{ route('flashsales') }}">Flash Sale</a>
                        <a href="{{ route('hotdeals') }}">Offers & Deals</a>
                        @foreach(($menucategories ?? collect())->take(3) as $category)
                            <a href="{{ route('category',$category->slug) }}">{{ $category->name }}</a>
                        @endforeach
                    </div>
                    {{-- Customer Care --}}
                    <div class="footer-col">
                        <h6>Customer Care</h6>
                        <a href="{{ route('customer.order_track') }}">Track Order</a>
                        <a href="{{ route('customer.account') }}">My Account</a>
                        <a href="{{ route('customer.orders') }}">My Orders</a>
                        <a href="{{ route('customer.refunds') }}">Return & Refund</a>
                        <a href="{{ route('complaint') }}">Complaints</a>
                        <a href="{{ route('contact') }}">Contact Us</a>
                    </div>
                    {{-- Information --}}
                    <div class="footer-col">
                        <h6>Information</h6>
                        @foreach($pages ?? [] as $page)
                        <a href="{{ route('page',$page->slug) }}">{{ $page->name }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Bottom --}}
        <div class="fashion-footer-bottom">
            <div class="fashion-container">
                <div class="footer-bottom">
                    <p>{{ $generalsetting->copyright ?? ('© '.date('Y').' '.optional($generalsetting)->name.'. All rights reserved.') }}</p>
                    <div class="footer-payments">
                        <span>We Accept</span>
                        <i class="fa-brands fa-cc-visa" title="Visa"></i>
                        <i class="fa-brands fa-cc-mastercard" title="Mastercard"></i>
                        <i class="fa-solid fa-money-bill-wave" title="Cash on Delivery"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    {{-- ===== EXISTING STOREFRONT FEATURES (kept while applying AVORONNO design) ===== --}}
    @php
        $popup = \Illuminate\Support\Facades\Cache::remember('active_frontend_popup', 300, function () {
            return \App\Models\Popup::where('status', 1)->latest()->first();
        });
    @endphp
    @if($popup && !empty(trim($popup->image ?? '')))
        <div id="sfPopup" class="sf-popup" aria-hidden="true">
            <div class="sf-popup__card">
                <button type="button" class="sf-popup__close" onclick="sfClosePopup()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                <a href="{{ !empty(trim($popup->link ?? '')) ? $popup->link : 'javascript:void(0)' }}" {{ !empty(trim($popup->link ?? '')) ? 'target="_blank"' : '' }}>
                    <img src="{{ asset($popup->image) }}" alt="Offer">
                    @if(!empty(trim($popup->description ?? '')) || !empty(trim($popup->btn_text ?? '')))
                        <div class="sf-popup__body">
                            @if(!empty(trim($popup->description ?? '')))<p>{!! nl2br(e($popup->description)) !!}</p>@endif
                            @if(!empty(trim($popup->btn_text ?? '')))<span class="sf-btn sf-btn--dark sf-btn--sm">{{ $popup->btn_text }}</span>@endif
                        </div>
                    @endif
                </a>
            </div>
        </div>
        <script>
            function sfClosePopup(){var popup=document.getElementById('sfPopup');if(popup)popup.classList.remove('show');try{localStorage.setItem('sf_popup_closed',Date.now())}catch(e){}}
            document.addEventListener('DOMContentLoaded',function(){
                var popup=document.getElementById('sfPopup');if(!popup)return;
                var closed=null;try{closed=localStorage.getItem('sf_popup_closed')}catch(e){}
                if(closed&&Date.now()-Number(closed)<864e5)return;
                setTimeout(function(){popup.classList.add('show')},1800);
                popup.addEventListener('click',function(e){if(e.target===popup)sfClosePopup()});
            });
        </script>
    @endif

    <div class="sf-chat">
        <div class="sf-chat__opts" id="sfChatOpts">
            @if(!empty(optional($generalsetting)->facebook_page_username))
                <a class="sf-chat__btn sf-chat__btn--msg" href="https://m.me/{{ $generalsetting->facebook_page_username }}" target="_blank" rel="noopener" aria-label="Messenger"><i class="fab fa-facebook-messenger"></i></a>
            @endif
            @if(!empty(optional($contact)->whatsapp))
                <a class="sf-chat__btn sf-chat__btn--wa" href="https://wa.me/{{ $contact->whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            @endif
            @if(!empty(optional($contact)->hotline))
                <a class="sf-chat__btn sf-chat__btn--call" href="tel:{{ $contact->hotline }}" aria-label="Call"><i class="fa-solid fa-phone"></i></a>
            @endif
        </div>
        <button class="sf-chat__btn sf-chat__btn--main" id="sfChatMain" type="button" aria-label="Chat with us"><i class="fa-solid fa-comment-dots"></i></button>
    </div>

    <div class="sf-cartdrawer-ovl" id="sfCartDrawerOvl" onclick="closeSidebarCart()"></div>
    <aside class="sf-cartdrawer" id="sfCartDrawer" aria-label="Shopping cart">
        <div id="sidebarCartContent"></div>
    </aside>

    {{-- The old quick button and its complete popup/order flow stay unchanged. --}}
    @include('frontEnd.layouts.partials.quick-order-modal')

    <div id="custom-modal"></div>
    <div id="page-overlay"></div>
    <div id="loading" aria-hidden="true"><div class="custom-loader"></div></div>

    {{-- ===== MOBILE BOTTOM NAV ===== --}}
    <nav class="mobile-nav">
        <a href="{{ route('home') }}" class="{{ request()->is('/') ? 'active' : '' }}">
            <i class="fa-solid fa-house"></i>Home
        </a>
        <a href="{{ route('hotdeals') }}" class="{{ request()->is('hot-deals') ? 'active' : '' }}">
            <i class="fa-solid fa-tags"></i>Offers
        </a>
        <a href="#" id="mobile-search-focus-bottom">
            <i class="fa-solid fa-magnifying-glass"></i>Search
        </a>
        <a href="{{ route('customer.checkout') }}" class="{{ request()->is('checkout') ? 'active' : '' }}">
            <i class="fa-solid fa-bag-shopping"></i>Cart
        </a>
        <a href="{{ Auth::guard('customer')->check() ? route('customer.account') : route('customer.login') }}">
            <i class="fa-regular fa-user"></i>Account
        </a>
    </nav>

    {{-- ===== BACK TO TOP ===== --}}
    <button class="back-to-top" id="backToTop" aria-label="Back to top">
        <i class="fa-solid fa-chevron-up"></i>
    </button>

    {{-- ===== SCRIPTS ===== --}}
    <script>window.SF=window.SF||{};SF.searchUrl="{{ route('livesearch') }}";SF.cartSidebarUrl="{{ route('cart.sidebar') }}";</script>
    <script src="{{ asset('public/frontEnd/js/jquery-3.6.3.min.js') }}"></script>
    <script>if(!window.jQuery){document.write('<script src="https://code.jquery.com/jquery-3.6.0.min.js"><\/script>')}</script>
    <script src="{{ asset('public/frontEnd/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('public/frontEnd/js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('public/frontEnd/js/storefront.js') }}?v=3"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js"></script>
    <script>if(window.feather){feather.replace()}</script>
    <script src="{{ asset('public/backEnd/assets/js/toastr.min.js') }}"></script>
    {!! Toastr::message() !!}
    <script>
    (function(){
        /* ---- Announcement Bar ---- */
        if(localStorage.getItem('announce_closed')){
            document.getElementById('announceBar').classList.add('hidden');
        }
        document.getElementById('announceClose').addEventListener('click',function(){
            document.getElementById('announceBar').classList.add('hidden');
            localStorage.setItem('announce_closed','1');
        });

        /* ---- Sticky Header Shadow ---- */
        var header=document.getElementById('siteHeader');
        window.addEventListener('scroll',function(){
            if(window.scrollY>10){
                header.classList.add('is-scrolled');
            }else{
                header.classList.remove('is-scrolled');
            }
        },{passive:true});

        /* ---- Back to Top ---- */
        var btt=document.getElementById('backToTop');
        window.addEventListener('scroll',function(){
            if(window.scrollY>400){
                btt.classList.add('visible');
            }else{
                btt.classList.remove('visible');
            }
        },{passive:true});
        btt.addEventListener('click',function(){
            window.scrollTo({top:0,behavior:'smooth'});
        });

        /* ---- Mobile Drawer ---- */
        var drawer=document.getElementById('mobileDrawer');
        var overlay=document.getElementById('mobileOverlay');
        function openDrawer(){
            drawer.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow='hidden';
        }
        function closeDrawer(){
            drawer.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow='';
        }
        document.getElementById('drawerOpen').addEventListener('click',openDrawer);
        document.getElementById('drawerClose').addEventListener('click',closeDrawer);
        overlay.addEventListener('click',closeDrawer);

        /* Drawer accordion */
        document.querySelectorAll('.drawer-link').forEach(function(link){
            var sub=link.nextElementSibling;
            if(!sub||!sub.classList.contains('drawer-sub'))return;
            link.addEventListener('click',function(e){
                e.preventDefault();
                this.classList.toggle('expanded');
                sub.classList.toggle('open');
            });
        });

        /* ---- Live Search ---- */
        var timer,req;
        function search(keyword){
            clearTimeout(timer);
            if(keyword.trim().length<2){$('.search-result').empty().removeClass('has-results');return}
            timer=setTimeout(function(){
                if(req)req.abort();
                req=$.get('{{ route('livesearch') }}',{keyword:keyword.trim()},function(html){
                    $('.search-result').html(html);
                    if($.trim(html)){
                        $('.search-result').addClass('has-results');
                    }else{
                        $('.search-result').removeClass('has-results');
                    }
                });
            },260);
        }
        $('.desktop-search,.mobile-search-input').on('input',function(){search($(this).val())});

        /* ---- Mobile Search Focus ---- */
        $('#mobile-search-focus-bottom').on('click',function(e){
            e.preventDefault();
            $('.mobile-search-input').focus();
            window.scrollTo({top:0,behavior:'smooth'});
        });
        /* Mobile search form submit */
        $('#mobileSearchBtn').on('click',function(){
            var kw=$('.mobile-search-input').val().trim();
            if(kw.length>=2){
                window.location.href='{{ route('search') }}?keyword='+encodeURIComponent(kw);
            }
        });
        $('.mobile-search-input').on('keypress',function(e){
            if(e.which===13){
                var kw=$(this).val().trim();
                if(kw.length>=2){
                    window.location.href='{{ route('search') }}?keyword='+encodeURIComponent(kw);
                }
            }
        });

        /* ---- Close search on outside click ---- */
        $(document).on('click',function(e){
            if(!$(e.target).closest('.fashion-search').length){
                $('.search-result').empty().removeClass('has-results');
            }
        });

    })();
    </script>

    <script>
    /* Existing cart, quick-view and checkout behavior retained under the new design. */
    (function($){
        if(!$) return;
        $('#loading').hide();

        window.openSidebarCart=function(){
            if(window.SF&&typeof SF.openCartDrawer==='function') SF.openCartDrawer();
            else {$('#sfCartDrawer,#sfCartDrawerOvl').addClass('show');$('body').addClass('sf-locked')}
            window.sidebarCartRefresh();
        };
        window.closeSidebarCart=function(){
            if(window.SF&&typeof SF.closeCartDrawer==='function') SF.closeCartDrawer();
            else {$('#sfCartDrawer,#sfCartDrawerOvl').removeClass('show');$('body').removeClass('sf-locked')}
        };
        window.sidebarCartRefresh=function(){
            $.get("{{ route('cart.sidebar') }}",function(html){$('#sidebarCartContent').html(html)});
        };
        $(document).on('click','[data-open-cart]',function(e){e.preventDefault();openSidebarCart()});

        window.refreshFashionCartCount=function(){
            $.get("{{ route('cart.count') }}",function(html){
                var count=$('<div>').html(html).find('.cart_count').first().text().trim();
                if(count!=='') $('.js-cart-count,.mobilecart-qty').text(count);
            });
        };

        $(document).on('click','.quick_view',function(e){
            e.preventDefault();e.stopPropagation();
            var id=$(this).data('id');if(!id)return;
            $('#loading').css('display','flex');
            $.get("{{ route('quickview') }}",{id:id}).done(function(html){
                $('#custom-modal').html(html).css('display','flex');
                $('#page-overlay').show().addClass('show');
            }).always(function(){$('#loading').hide()});
        });
        $(document).on('click','#page-overlay',function(){$('#custom-modal,#page-overlay').hide();$('#page-overlay').removeClass('show')});

        function flyToCart($source,done){
            var $img=$source.closest('.product-card-v2,.sf-pd,.product_item,.wist_item').find('img').first();
            var $target=$('.action-item[href*="checkout"]:visible,.mobile-cart:visible').first();
            if(!$img.length||!$target.length){if(done)done();return}
            var from=$img[0].getBoundingClientRect(),to=$target[0].getBoundingClientRect();
            $img.clone().addClass('fly-to-cart-img').css({position:'fixed',left:from.left,top:from.top,width:72,height:86,objectFit:'cover',zIndex:99999,borderRadius:10,boxShadow:'0 8px 24px rgba(0,0,0,.25)'}).appendTo('body').animate({left:to.left,top:to.top,width:30,height:36,opacity:.25},480,function(){$(this).remove();if(done)done()});
        }

        $(document).on('click','.addcartbutton',function(e){
            e.preventDefault();e.stopPropagation();
            var $button=$(this),id=$button.data('id');if(!id)return;
            $button.prop('disabled',true);
            $.get("{{ url('add-to-cart') }}/"+id+'/1').done(function(data){
                if(data){toastr.success('Product added to cart','Success');refreshFashionCartCount();flyToCart($button,openSidebarCart)}
            }).fail(function(){toastr.error('Could not add this product')}).always(function(){$button.prop('disabled',false)});
        });

        $(document).on('click','.cart_store',function(e){
            var $button=$(this),$form=$button.closest('form');if(!$form.length)return;
            e.preventDefault();
            $.ajax({type:'POST',url:$form.attr('action'),data:$form.serialize(),headers:{'X-Requested-With':'XMLHttpRequest'},dataType:'json'})
                .done(function(data){if(data&&data.success){toastr.success('Product added to cart','Success');refreshFashionCartCount();flyToCart($button,openSidebarCart)}else toastr.error(data&&data.message?data.message:'Failed')})
                .fail(function(xhr){if(xhr.responseJSON&&xhr.responseJSON.message)toastr.error(xhr.responseJSON.message);else $form[0].submit()});
        });

        function updateCart(url,id,refreshSummary){
            if(!id)return;
            $.get(url,{id:id}).done(function(html){
                if(html) $('.cartlist').html(html);
                refreshFashionCartCount();
                if(refreshSummary) $.get("{{ route('shipping.charge') }}",function(summary){$('.cart-summary').html(summary)});
                if($('#sfCartDrawer').hasClass('show')) sidebarCartRefresh();
            });
        }
        $(document).on('click','.cart_remove',function(e){e.preventDefault();updateCart("{{ route('cart.remove') }}",$(this).data('id'),true)});
        $(document).on('click','.cart_increment',function(e){e.preventDefault();updateCart("{{ route('cart.increment') }}",$(this).data('id'),false)});
        $(document).on('click','.cart_decrement',function(e){e.preventDefault();updateCart("{{ route('cart.decrement') }}",$(this).data('id'),false)});

        $(document).on('change','.district',function(){
            $.get("{{ route('districts') }}",{id:$(this).val()},function(areas){
                var $area=$('.area').empty().append('<option value="">Select area…</option>');
                $.each(areas||{},function(key,value){$area.append($('<option>').val(key).text(value))});
            });
        });
    })(window.jQuery);
    </script>
    @stack('script')
    <script>if('serviceWorker' in navigator&&location.protocol==='https:'){window.addEventListener('load',function(){navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(function(){})})}</script>
</body>
</html>
