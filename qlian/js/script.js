document.addEventListener('DOMContentLoaded', function() {
    // CSRF защита
    function generateCSRF() {
        return Math.random().toString(36).substring(2, 15) + 
               Math.random().toString(36).substring(2, 15);
    }
    
    // Установка CSRF токенов
    const csrfFields = document.querySelectorAll('input[name="csrf_token"]');
    csrfFields.forEach(field => {
        if (!field.value) {
            field.value = generateCSRF();
        }
    });

    // Мобильное меню
    const burger = document.getElementById('burger-btn');
    const nav = document.getElementById('nav-menu');
    
    if (burger && nav) {
        burger.addEventListener('click', function() {
            const isOpen = nav.classList.toggle('active');
            burger.setAttribute('aria-expanded', isOpen);
        });
    }

    // Закрытие меню при клике вне его
    document.addEventListener('click', function(e) {
        if (nav && nav.classList.contains('active') && 
            !e.target.closest('.nav') && 
            !e.target.closest('.burger-menu')) {
            nav.classList.remove('active');
            burger?.setAttribute('aria-expanded', 'false');
        }
    });

    // Модальное окно с политикой
    const privacyModal = document.getElementById('privacy-modal');
    const privacyLinks = document.querySelectorAll('.privacy-link-trigger, [href="#privacy-modal"]');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    
    privacyLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href') === '#privacy-modal' || 
                this.classList.contains('privacy-link-trigger')) {
                e.preventDefault();
                if (privacyModal) {
                    privacyModal.style.display = 'block';
                    privacyModal.setAttribute('aria-hidden', 'false');
                }
            }
        });
    });
    
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
            }
        });
    });
    
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
            e.target.setAttribute('aria-hidden', 'true');
        }
    });

    // Валидация и отправка форм
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Проверка телефона
            const phoneInput = form.querySelector('input[name="phone"]');
            if (phoneInput && phoneInput.value.trim()) {
                const phoneValue = phoneInput.value.replace(/\s+/g, '');
                const phonePattern = /^\+7\d{10}$/;
                
                if (!phonePattern.test(phoneValue)) {
                    e.preventDefault();
                    alert('Пожалуйста, введите корректный номер телефона в формате +7XXXXXXXXXX');
                    phoneInput.focus();
                    isValid = false;
                    return;
                }
            }
            
            // Проверка чекбокса согласия
            const privacyCheck = form.querySelector('input[name="privacy"]');
            if (privacyCheck && !privacyCheck.checked) {
                e.preventDefault();
                alert('Необходимо дать согласие на обработку персональных данных');
                isValid = false;
                return;
            }
        });
    });

    // Калькулятор
    const calcForm = document.getElementById('calculator-form');
    if (calcForm) {
        calcForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const type = document.getElementById('service-type').value;
            const area = parseFloat(document.getElementById('area').value);
            const urgent = document.getElementById('urgent').checked;
            
            if (!type || !area) {
                alert('Пожалуйста, заполните все поля для расчета');
                return;
            }

            if (area < 10 || area > 10000) {
                alert('Площадь должна быть от 10 до 10000 м²');
                return;
            }

            const prices = {
                flat: 80,
                house: 90,
                office: 70,
                renovation: 150,
                windows: 100,
                furniture: 250
            };

            let basePrice = prices[type] || 80;
            let total = area * basePrice;
            
            if (urgent) total *= 1.3;
            if (area < 30) total = Math.max(total, basePrice * 25);

            const resultDiv = document.getElementById('calc-result');
            resultDiv.innerHTML = `
                <div class="calc-result-content">
                    <strong>Примерная стоимость:</strong> от ${Math.round(total).toLocaleString('ru-RU')} ₽
                    <p class="calc-disclaimer">*Точная стоимость рассчитывается после осмотра объекта</p>
                </div>
            `;
            resultDiv.style.display = 'block';
            resultDiv.scrollIntoView({ behavior: 'smooth' });
        });
    }

    // Отправка формы быстрой заявки через AJAX
    const quickForm = document.getElementById('quick-form');
    if (quickForm) {
        quickForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Спасибо! Ваша заявка принята. Номер заказа: ' + result.order_number);
                    this.reset();
                } else {
                    alert(result.error || 'Произошла ошибка. Попробуйте позже или позвоните нам.');
                }
            } catch (error) {
                console.error('Ошибка отправки:', error);
                alert('Не удалось отправить заявку. Пожалуйста, позвоните нам по телефону.');
            }
        });
    }
});