<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pizza Mach</title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;600;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/pages/main.css">
    <style>
        html {
            scroll-behavior: smooth;
        }

        /* Navigation CTA styling */
        .nav-cta {
            background: #ff3b30 !important;
            color: white !important;
            padding: 0.5rem 1.25rem !important;
            border-radius: 8px !important;
            margin-left: 1rem !important;
            transition: all 0.2s ease !important;
        }

        .nav-cta:hover {
            background: #e63229 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 59, 48, 0.3);
        }

        /* About section */
        .about-section {
            padding: 4rem 4rem;
            background: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        .about-content {
            max-width: 1000px;
            margin: 0 auto;
            text-align: center;
        }

        .about-content h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            letter-spacing: -1px;
        }

        .about-content > p {
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 3rem;
            color: #555;
        }

        .about-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .detail-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            text-align: left;
            padding: 1.5rem;
            background: #fafafa;
            border-radius: 12px;
        }

        .detail-item .material-icons {
            font-size: 2rem;
            color: #ff3b30;
        }

        .detail-item strong {
            display: block;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .detail-item p {
            color: #666;
            margin: 0;
        }

        /* Contact section */
        .contact-section {
            padding: 4rem 4rem;
            background: #fafafa;
        }

        .contact-content {
            max-width: 1000px;
            margin: 0 auto;
            text-align: center;
        }

        .contact-content h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -1px;
        }

        .contact-content > p {
            font-size: 1.1rem;
            margin-bottom: 3rem;
            color: #555;
        }

        .contact-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-align: left;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .contact-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .contact-item .material-icons {
            font-size: 2.5rem;
            color: #ff3b30;
        }

        .contact-item strong {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .contact-item a {
            color: #1a1a1a;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: 600;
            transition: color 0.2s;
        }

        .contact-item a:hover {
            color: #ff3b30;
        }

        /* Footer improvements */
        footer {
            background: #1a1a1a;
            color: white;
            padding: 3rem 4rem 1.5rem;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.5fr 2fr;
            gap: 4rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-logo {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .footer-logo-img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
        }

        .footer-brand {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .footer-tagline {
            font-size: 0.9rem;
            opacity: 0.7;
            margin: 0;
        }

        .footer-links {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .footer-section h4 {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
            opacity: 0.5;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .footer-section a,
        .footer-section p {
            color: white;
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 300;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .footer-section a:hover {
            opacity: 1;
        }

        .footer-bottom {
            max-width: 1400px;
            margin: 0 auto;
            padding-top: 1.5rem;
            text-align: center;
        }

        .footer-copyright {
            font-size: 0.85rem;
            opacity: 0.5;
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .about-section,
            .contact-section {
                padding: 3rem 2rem;
            }

            .about-content h2,
            .contact-content h2 {
                font-size: 2rem;
            }

            .about-details,
            .contact-methods {
                grid-template-columns: 1fr;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .footer-links {
                grid-template-columns: 1fr;
            }

            nav {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .nav-cta {
                margin-left: 0 !important;
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="hero">
        <header class="main-header">
            <a href="main.php" class="logo" aria-label="Pizza Mach ホーム">
                <img src="./assets/image/logo.png" alt="Pizza Mach" class="header-logo-circle">
                <span class="logo-text">Pizza Mach</span>
            </a>
            <nav aria-label="メインナビゲーション">
                <a href="#about">店舗情報</a>
                <a href="#contact">お問い合わせ</a>
                <a href="index.php" class="nav-cta">メニューを見る</a>
            </nav>
        </header>

        <div class="content">
            <div class="content-grid">
                <div class="hero-text">
                    <h1 class="slogan">
                        <span class="slogan-line">一度たべたら</span>
                        <span class="slogan-line highlight">忘れられない</span>
                        <span class="slogan-line">ピザマッハ</span>
                    </h1>
                    <p class="slogan-accent" aria-hidden="true"></p>
                    <p class="tagline">シンプルに、美味しく、あなたのために</p>
                    <a href="index.php" class="cta-button">
                        <span>今すぐ注文</span>
                        <span class="material-icons">arrow_forward</span>
                    </a>

                    <div class="stats">
                        <div class="stat">
                            <div class="stat-number">30分以内</div>
                            <div class="stat-label">最速配達</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number">4.9★</div>
                            <div class="stat-label">お客様の評価</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section id="about" class="about-section">
            <div class="about-content">
                <h2>ピザマッハについて</h2>
                <p>横浜市中区にある、こだわりのピザ店です。<br>
                   厳選された食材と伝統的な製法で、<br>
                   一枚一枚心を込めて焼き上げています。</p>
                <div class="about-details">
                    <div class="detail-item">
                        <span class="material-icons">schedule</span>
                        <div>
                            <strong>営業時間</strong>
                            <p>11:00 - 22:00 (年中無休)</p>
                        </div>
                    </div>
                    <div class="detail-item">
                        <span class="material-icons">location_on</span>
                        <div>
                            <strong>所在地</strong>
                            <p>神奈川県横浜市中区</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="contact" class="contact-section">
            <div class="contact-content">
                <h2>お問い合わせ</h2>
                <p>ご注文・ご質問はお気軽にお問い合わせください。</p>
                <div class="contact-methods">
                    <div class="contact-item">
                        <span class="material-icons">phone</span>
                        <div>
                            <strong>電話</strong>
                            <a href="tel:0451234567">045-123-4567</a>
                        </div>
                    </div>
                    <div class="contact-item">
                        <span class="material-icons">email</span>
                        <div>
                            <strong>メール</strong>
                            <a href="mailto:hello@pizzamach.jp">hello@pizzamach.jp</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <footer>
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="./assets/image/logo.png" alt="Pizza Mach" class="footer-logo-img">
                    <p class="footer-brand">Pizza Mach</p>
                    <p class="footer-tagline">シンプルに、美味しく、あなたのために</p>
                </div>
                <div class="footer-links">
                    <div class="footer-section">
                        <h4>メニュー</h4>
                        <a href="index.php">ピザメニュー</a>
                        <a href="index.php">注文する</a>
                    </div>
                    <div class="footer-section">
                        <h4>店舗情報</h4>
                        <a href="#about">ピザマッハについて</a>
                        <p>横浜市中区<br>11:00 - 22:00</p>
                    </div>
                    <div class="footer-section">
                        <h4>お問い合わせ</h4>
                        <a href="tel:0451234567">045-123-4567</a>
                        <a href="mailto:hello@pizzamach.jp">hello@pizzamach.jp</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="footer-copyright">© 2026 Pizza Mach (ピザマッハ). All rights reserved.</p>
            </div>
        </footer>
    </div>
</body>
</html>