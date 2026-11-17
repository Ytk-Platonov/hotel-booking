document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bookingForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        let isValid = true;
        const errors = [];

        const firstName = form.first_name.value.trim();
        const lastName = form.last_name.value.trim();
        const phone = form.phone.value.trim();
        const email = form.email.value.trim();
        const checkIn = form.check_in.value.trim();
        const checkOut = form.check_out.value.trim();

        // Валидация имени и фамилии
        const nameRegex = /^[А-Яа-яЁё\s.\-]+$/u;
        if (!nameRegex.test(firstName)) {
            errors.push('Имя: только кириллица, пробелы, точки и тире.');
            isValid = false;
        }
        if (!nameRegex.test(lastName)) {
            errors.push('Фамилия: только кириллица, пробелы, точки и тире.');
            isValid = false;
        }

        // Валидация телефона
        const phoneRegex = /^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/;
        if (!phoneRegex.test(phone)) {
            errors.push('Телефон должен быть в формате +7(XXX)XXX-XX-XX');
            isValid = false;
        }

        // Валидация email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            errors.push('Некорректный формат E-mail.');
            isValid = false;
        }

        // Валидация дат
        const dateRegex = /^\d{2}\.\d{2}\.\d{4}$/;
        if (!dateRegex.test(checkIn) || !dateRegex.test(checkOut)) {
            errors.push('Даты должны быть в формате ДД.ММ.ГГГГ.');
            isValid = false;
        } else {
            const [d1, m1, y1] = checkIn.split('.');
            const [d2, m2, y2] = checkOut.split('.');
            const dateIn = new Date(y1, m1 - 1, d1);
            const dateOut = new Date(y2, m2 - 1, d2);
            if (dateIn >= dateOut) {
                errors.push('Дата заезда должна быть раньше даты выезда.');
                isValid = false;
            }
        }

        if (!isValid) {
            e.preventDefault();
            alert('Ошибки валидации:\n\n' + errors.join('\n'));
        }
    });
});
