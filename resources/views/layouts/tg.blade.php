<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Выбор роли</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        .main-container{
            display: grid;
            height: 100vh;
            align-content: space-between;
        }
        .rounded-icon-button {
            background-color: #e4e4e4;
            border: none;
            border-radius: 999px;
            padding: 10px 16px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .rounded-icon-button svg {
            width: 20px;
            height: 20px;
            fill: #555;
        }

        .dot {
            width: 6px;
            height: 6px;
            background-color: #808080;
            border-radius: 50%;
            display: inline-block;
            margin: 0 1.5px;
        }

        .btn-close-style {
            background-color: #e4e4e4;
            border: none;
            border-radius: 999px;
            padding: 8px 18px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 18px;
            color: #555;
        }

        .btn-close-style svg {
            width: 20px;
            height: 20px;
            stroke: #555;
            stroke-width: 2;
        }

        .text-left{
            text-align: left!important;
        }
    </style>

    @yield('meta')
</head>
<body>
<div class="container py-5 main-container">
    <header class="w-100 d-flex justify-content-between">
        <button class="btn-close-style">
            <!-- Крестик (иконка X) -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path d="M6 6l12 12M6 18L18 6" stroke-linecap="round" />
            </svg>
            Закрыть
        </button>
        <div class="logo">
            <svg width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g filter="url(#filter0_dd_1754_5503)">
                    <g clip-path="url(#clip0_1754_5503)">
                        <rect x="3" y="2" width="32" height="32" rx="8" fill="white"/>
                        <rect x="3" y="2" width="32" height="32" rx="8" fill="url(#paint0_linear_1754_5503)"/>
                    </g>
                    <rect x="3.15" y="2.15" width="31.7" height="31.7" rx="7.85" stroke="#D0D5DD" stroke-width="0.3"/>
                </g>
                <g filter="url(#filter1_d_1754_5503)">
                    <path d="M12 11H29C29.5523 11 30 11.4477 30 12V24.6627C30 27.0582 28.0102 29 25.5556 29H16.4444C13.9898 29 12 27.0582 12 24.6627V11Z" fill="#936DE8"/>
                </g>
                <g filter="url(#filter2_d_1754_5503)">
                    <path d="M9 12C9 11.4477 9.44772 11 10 11H27V24.6627C27 27.0582 25.0102 29 22.5556 29H13.4444C10.9898 29 9 27.0582 9 24.6627V12Z" fill="url(#paint1_linear_1754_5503)"/>
                </g>
                <g filter="url(#filter3_d_1754_5503)">
                    <path d="M17.5556 19.0382L27 14V24.4198C27 26.9495 25.0102 29 22.5556 29H13.4444C10.9898 29 9 26.9495 9 24.4198V23.7328L17.5556 19.0382Z" fill="url(#paint2_linear_1754_5503)"/>
                </g>
                <g filter="url(#filter4_d_1754_5503)">
                    <path d="M27 11C27 9.67392 26.4732 8.40215 25.5355 7.46446C24.5979 6.52679 23.3261 6 22 6C20.6739 6 19.4021 6.52679 18.4645 7.46446C17.5268 8.40215 17 9.67392 17 11H18.2955C18.2955 10.0175 18.6858 9.07527 19.3805 8.38054C20.0753 7.68581 21.0175 7.29552 22 7.29552C22.9825 7.29552 23.9247 7.68581 24.6195 8.38054C25.3142 9.07527 25.7045 10.0175 25.7045 11H27Z" fill="#936DE8"/>
                </g>
                <g filter="url(#filter5_d_1754_5503)">
                    <path d="M24 11C24 9.67392 23.3678 8.40215 22.2426 7.46446C21.1174 6.52679 19.5913 6 18 6C16.4087 6 14.8826 6.52679 13.7573 7.46446C12.6321 8.40215 12 9.67392 12 11H13.5546C13.5546 10.0175 14.023 9.07527 14.8567 8.38054C15.6903 7.68581 16.821 7.29552 18 7.29552C19.179 7.29552 20.3097 7.68581 21.1434 8.38054C21.977 9.07527 22.4454 10.0175 22.4454 11H24Z" fill="#7F56D9"/>
                </g>
                <g filter="url(#filter6_d_1754_5503)">
                    <path d="M14.5827 19.3389L13.9149 21H13.1455L12 18H12.9812L13.5907 19.5983L14.2326 18H14.9328L15.5747 19.5983L16.1842 18H17.1654L16.0199 21H15.2505L14.5827 19.3389ZM17.4896 21V18H19.6314C19.7986 18 19.9434 18.0126 20.0658 18.0377C20.1898 18.0614 20.2956 18.0941 20.3835 18.136C20.4714 18.1778 20.5435 18.2266 20.5997 18.2824C20.6559 18.3382 20.6998 18.3975 20.7315 18.4602C20.7646 18.523 20.787 18.5879 20.7985 18.6548C20.8115 18.7204 20.8179 18.7845 20.8179 18.8473C20.8179 18.917 20.8071 18.9826 20.7855 19.0439C20.7639 19.1053 20.7322 19.1611 20.6904 19.2113C20.6486 19.2615 20.5975 19.3054 20.537 19.3431C20.4779 19.3794 20.4109 19.4086 20.336 19.431C20.4469 19.4547 20.5399 19.4902 20.6148 19.5376C20.6912 19.5851 20.7517 19.6395 20.7963 19.7008C20.8424 19.7622 20.8749 19.8291 20.8936 19.9017C20.9138 19.9742 20.9238 20.0481 20.9238 20.1234C20.9238 20.1904 20.9159 20.2587 20.9001 20.3284C20.8842 20.3982 20.8576 20.4658 20.8201 20.5314C20.7826 20.5955 20.7322 20.6562 20.6688 20.7134C20.6069 20.7706 20.5291 20.8208 20.4354 20.864C20.3417 20.9058 20.2315 20.9393 20.1047 20.9644C19.9779 20.9882 19.831 21 19.6638 21H17.4896ZM19.6638 20.3075C19.9131 20.3075 20.0377 20.2232 20.0377 20.0544C20.0377 19.8856 19.9131 19.8013 19.6638 19.8013H18.4038V20.3075H19.6638ZM19.6314 19.1715C19.6905 19.1715 19.7395 19.1646 19.7784 19.1506C19.8187 19.1367 19.8504 19.1185 19.8735 19.0962C19.898 19.0739 19.9153 19.0488 19.9253 19.0209C19.9354 18.9916 19.9405 18.9623 19.9405 18.9331C19.9405 18.9038 19.9354 18.8752 19.9253 18.8473C19.9153 18.818 19.898 18.7922 19.8735 18.7699C19.8504 18.7476 19.8187 18.7294 19.7784 18.7155C19.7395 18.7015 19.6905 18.6946 19.6314 18.6946H18.4038V19.1715H19.6314ZM25 19.5C25 19.712 24.9712 19.9094 24.9136 20.092C24.8574 20.2734 24.7651 20.4317 24.6369 20.5669C24.5087 20.7022 24.3408 20.8082 24.1333 20.8849C23.9273 20.9617 23.6752 21 23.3769 21H21.3583V18H23.3769C23.6752 18 23.9273 18.0391 24.1333 18.1172C24.3408 18.1939 24.5087 18.2999 24.6369 18.4351C24.7651 18.569 24.8574 18.7273 24.9136 18.91C24.9712 19.0927 25 19.2894 25 19.5ZM22.2725 20.2636H23.3769C23.6218 20.2636 23.8005 20.2001 23.9129 20.0732C24.0253 19.9449 24.0815 19.7538 24.0815 19.5C24.0815 19.2462 24.0253 19.0558 23.9129 18.9289C23.8005 18.8006 23.6218 18.7364 23.3769 18.7364H22.2725V20.2636Z" fill="white"/>
                </g>
                <defs>
                    <filter id="filter0_dd_1754_5503" x="0" y="0" width="38" height="38" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                        <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                        <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                        <feOffset dy="1"/>
                        <feGaussianBlur stdDeviation="1"/>
                        <feColorMatrix type="matrix" values="0 0 0 0 0.0627451 0 0 0 0 0.0941176 0 0 0 0 0.156863 0 0 0 0.06 0"/>
                        <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1754_5503"/>
                        <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                        <feOffset dy="1"/>
                        <feGaussianBlur stdDeviation="1.5"/>
                        <feColorMatrix type="matrix" values="0 0 0 0 0.0627451 0 0 0 0 0.0941176 0 0 0 0 0.156863 0 0 0 0.1 0"/>
                        <feBlend mode="normal" in2="effect1_dropShadow_1754_5503" result="effect2_dropShadow_1754_5503"/>
                        <feBlend mode="normal" in="SourceGraphic" in2="effect2_dropShadow_1754_5503" result="shape"/>
                    </filter>
                    <filter id="filter1_d_1754_5503" x="8" y="8" width="26" height="26" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                        <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                        <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                        <feOffset dy="1"/>
                        <feGaussianBlur stdDeviation="2"/>
                        <feComposite in2="hardAlpha" operator="out"/>
                        <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.15 0"/>
                        <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1754_5503"/>
                        <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1754_5503" result="shape"/>
                    </filter>
                    <filter id="filter2_d_1754_5503" x="5" y="8" width="26" height="26" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                        <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                        <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                        <feOffset dy="1"/>
                        <feGaussianBlur stdDeviation="2"/>
                        <feComposite in2="hardAlpha" operator="out"/>
                        <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.15 0"/>
                        <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1754_5503"/>
                        <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1754_5503" result="shape"/>
                    </filter>
                    <filter id="filter3_d_1754_5503" x="5" y="11" width="26" height="23" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                        <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                        <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                        <feOffset dy="1"/>
                        <feGaussianBlur stdDeviation="2"/>
                        <feComposite in2="hardAlpha" operator="out"/>
                        <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.15 0"/>
                        <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1754_5503"/>
                        <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1754_5503" result="shape"/>
                    </filter>
                    <filter id="filter4_d_1754_5503" x="13" y="3" width="18" height="13" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                        <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                        <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                        <feOffset dy="1"/>
                        <feGaussianBlur stdDeviation="2"/>
                        <feComposite in2="hardAlpha" operator="out"/>
                        <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.15 0"/>
                        <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1754_5503"/>
                        <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1754_5503" result="shape"/>
                    </filter>
                    <filter id="filter5_d_1754_5503" x="8" y="3" width="20" height="13" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                        <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                        <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                        <feOffset dy="1"/>
                        <feGaussianBlur stdDeviation="2"/>
                        <feComposite in2="hardAlpha" operator="out"/>
                        <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.15 0"/>
                        <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1754_5503"/>
                        <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1754_5503" result="shape"/>
                    </filter>
                    <filter id="filter6_d_1754_5503" x="8" y="15" width="21" height="11" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                        <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                        <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                        <feOffset dy="1"/>
                        <feGaussianBlur stdDeviation="2"/>
                        <feComposite in2="hardAlpha" operator="out"/>
                        <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.15 0"/>
                        <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1754_5503"/>
                        <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1754_5503" result="shape"/>
                    </filter>
                    <linearGradient id="paint0_linear_1754_5503" x1="19" y1="2" x2="19" y2="34" gradientUnits="userSpaceOnUse">
                        <stop stop-color="white"/>
                        <stop offset="1" stop-color="#D0D5DD"/>
                    </linearGradient>
                    <linearGradient id="paint1_linear_1754_5503" x1="17.8889" y1="13.9277" x2="4.57133" y2="24.1066" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#7F56D9"/>
                        <stop offset="1" stop-color="#432E73"/>
                    </linearGradient>
                    <linearGradient id="paint2_linear_1754_5503" x1="17.8889" y1="13.084" x2="25.8146" y2="28.4658" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#7F56D9"/>
                        <stop offset="1" stop-color="#432E73"/>
                    </linearGradient>
                    <clipPath id="clip0_1754_5503">
                        <rect x="3" y="2" width="32" height="32" rx="8" fill="white"/>
                    </clipPath>
                </defs>
            </svg>
        </div>

        <button class="rounded-icon-button">
            <!-- Down arrow icon (SVG) -->
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" width="960px" height="560px" viewBox="0 0 960 560" enable-background="new 0 0 960 560" xml:space="preserve">
            <g id="Rounded_Rectangle_33_copy_4_1_">
                <path d="M480,344.181L268.869,131.889c-15.756-15.859-41.3-15.859-57.054,0c-15.754,15.857-15.754,41.57,0,57.431l237.632,238.937   c8.395,8.451,19.562,12.254,30.553,11.698c10.993,0.556,22.159-3.247,30.555-11.698l237.631-238.937   c15.756-15.86,15.756-41.571,0-57.431s-41.299-15.859-57.051,0L480,344.181z"/>
            </g>
            </svg>

            <!-- Three dots -->
            <div>
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
        </button>

    </header>
    <div>
        @yield('content')
    </div>

    <div class="support mt-5 text-left">
        <strong>Есть вопросы?</strong><br/>
        Напишите в поддержку по любому вопросу и мы поможем вам. Мы на связи!<br/>
        <a href="https://t.me/wbd_sppport" target="_blank">@wbd_sppport →</a>
    </div>
</div>

@yield('scripts')
</body>
</html>
