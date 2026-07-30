class CookieConsent {
    constructor() {
        this.cookieName = 'cookie_consent';
        this.init();
    }
    
    init() {
        if (!this.getCookie(this.cookieName)) {
            this.showBanner();
        }
    }
    
    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
    
    setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${date.toUTCString()};path=/;SameSite=Lax`;
    }
    
    showBanner() {
        const banner = document.createElement('div');
        banner.className = 'cookie-banner';
        banner.innerHTML = `
            <div class="cookie-banner-content">
                <div class="cookie-text">
                    <i class="fas fa-cookie-bite"></i>
                    <p>Мы используем cookie-файлы для улучшения работы сайта. 
                    Продолжая использовать сайт, вы соглашаетесь с 
                    <a href="personal-data-policy.html" target="_blank">Политикой обработки персональных данных</a> 
                    и <a href="privacy-policy.html" target="_blank">Политикой конфиденциальности</a>.</p>
                </div>
                <div class="cookie-buttons">
                    <button class="cookie-btn cookie-accept" onclick="cookieConsent.accept()">
                        Принять
                    </button>
                    <button class="cookie-btn cookie-settings" onclick="cookieConsent.settings()">
                        Настроить
                    </button>
                    <button class="cookie-btn cookie-reject" onclick="cookieConsent.reject()">
                        Отказаться
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(banner);
        
        // Стили для баннера
        const style = document.createElement('style');
        style.textContent = `
            .cookie-banner {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
                z-index: 9999;
                padding: 20px;
                animation: slideUp 0.5s ease;
            }
            @keyframes slideUp {
                from { transform: translateY(100%); }
                to { transform: translateY(0); }
            }
            .cookie-banner-content {
                max-width: 1200px;
                margin: 0 auto;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 20px;
            }
            .cookie-text {
                display: flex;
                align-items: center;
                gap: 15px;
                flex: 1;
                min-width: 300px;
            }
            .cookie-text i {
                font-size: 32px;
                color: #D2691E;
            }
            .cookie-text a {
                color: #00BCD4;
                text-decoration: underline;
            }
            .cookie-buttons {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            .cookie-btn {
                padding: 10px 25px;
                border-radius: 25px;
                border: none;
                cursor: pointer;
                font-weight: 600;
                transition: all 0.3s;
            }
            .cookie-accept {
                background: #00BCD4;
                color: white;
            }
            .cookie-accept:hover {
                background: #0097A7;
            }
            .cookie-settings {
                background: #f0f0f0;
                color: #333;
            }
            .cookie-settings:hover {
                background: #e0e0e0;
            }
            .cookie-reject {
                background: white;
                color: #666;
                border: 1px solid #ccc;
            }
            .cookie-reject:hover {
                background: #f5f5f5;
            }
            @media (max-width: 768px) {
                .cookie-banner-content {
                    flex-direction: column;
                    text-align: center;
                }
                .cookie-text {
                    flex-direction: column;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    accept() {
        this.setCookie(this.cookieName, 'accepted', 365);
        this.setCookie('cookie_analytics', 'true', 365);
        this.removeBanner();
        // Включаем аналитику
        this.enableAnalytics();
    }
    
    settings() {
        // Можно добавить модальное окно с настройками
        this.showSettingsModal();
    }
    
    reject() {
        this.setCookie(this.cookieName, 'rejected', 365);
        this.setCookie('cookie_analytics', 'false', 365);
        this.removeBanner();
        // Отключаем все необязательные cookie
        this.disableAnalytics();
    }
    
    removeBanner() {
        const banner = document.querySelector('.cookie-banner');
        if (banner) {
            banner.style.animation = 'slideDown 0.5s ease';
            setTimeout(() => banner.remove(), 500);
        }
    }
    
    showSettingsModal() {
        const modal = document.createElement('div');
        modal.className = 'cookie-modal';
        modal.innerHTML = `
            <div class="cookie-modal-content">
                <h3>Настройки cookie</h3>
                <div class="cookie-option">
                    <label>
                        <input type="checkbox" checked disabled>
                        <strong>Необходимые cookie</strong>
                        <p>Требуются для работы сайта. Всегда включены.</p>
                    </label>
                </div>
                <div class="cookie-option">
                    <label>
                        <input type="checkbox" id="analytics-cookie" checked>
                        <strong>Аналитические cookie</strong>
                        <p>Помогают нам улучшать сайт, собирая обезличенную статистику.</p>
                    </label>
                </div>
                <button class="cookie-btn cookie-accept" onclick="cookieConsent.saveSettings()">
                    Сохранить настройки
                </button>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Стили для модального окна
        const modalStyle = document.createElement('style');
        modalStyle.textContent = `
            .cookie-modal {
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .cookie-modal-content {
                background: white;
                padding: 30px;
                border-radius: 20px;
                max-width: 500px;
                width: 90%;
            }
            .cookie-modal-content h3 {
                margin-bottom: 20px;
                color: #333;
            }
            .cookie-option {
                margin-bottom: 15px;
                padding: 10px;
                background: #f9f9f9;
                border-radius: 10px;
            }
            .cookie-option label {
                cursor: pointer;
            }
            .cookie-option p {
                font-size: 14px;
                color: #666;
                margin-top: 5px;
            }
        `;
        document.head.appendChild(modalStyle);
    }
    
    saveSettings() {
        const analytics = document.getElementById('analytics-cookie')?.checked;
        this.setCookie(this.cookieName, 'custom', 365);
        this.setCookie('cookie_analytics', analytics ? 'true' : 'false', 365);
        
        if (analytics) {
            this.enableAnalytics();
        } else {
            this.disableAnalytics();
        }
        
        document.querySelector('.cookie-modal')?.remove();
        this.removeBanner();
    }
    
    enableAnalytics() {
        // Здесь код для включения Яндекс.Метрики или Google Analytics
        console.log('Analytics enabled');
    }
    
    disableAnalytics() {
        // Здесь код для отключения аналитики
        console.log('Analytics disabled');
    }
}

// Инициализация при загрузке
const cookieConsent = new CookieConsent();