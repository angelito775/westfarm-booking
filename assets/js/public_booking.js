document.addEventListener('DOMContentLoaded', () => {

    // --- DOM ELEMENTS ---
    const cardsContainer = document.getElementById('cards');
    const searchInput = document.getElementById('search');
    const tabsContainer = document.getElementById('tabs');
    const calElement = document.getElementById('cal');
    const calTitle = document.getElementById('calTitle');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');
    const checkInEl = document.getElementById('checkIn');
    const checkOutEl = document.getElementById('checkOut');
    const nightsNumEl = document.getElementById('nightsNum');
    const adultsEl = document.getElementById('adults');
    const kidsEl = document.getElementById('kids');
    const countersContainer = document.querySelector('.counters');
    const bookBtn = document.getElementById('bookBtn');
    const copyrightInfo = document.getElementById('copyright-info');

    // --- STATE ---
    let accommodations = [];
    let categories = [];
    let currentDate = new Date();
    let checkInDate = null;
    let checkOutDate = null;
    let adults = 1;
    let kids = 0;
    let activeCategory = 'All';

    // --- FETCH DATA FROM DATABASE ---
    function loadFacilities() {
        fetch('../logic/get_public_facilities.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    accommodations = data.items;
                    categories = data.categories;
                    renderTabs();
                    renderCards(accommodations);
                } else {
                    cardsContainer.innerHTML = '<p>Unable to load accommodations. Please try again later.</p>';
                }
            })
            .catch(() => {
                cardsContainer.innerHTML = '<p>Unable to load accommodations. Please try again later.</p>';
            });
    }

    // --- RENDER CATEGORY TABS DYNAMICALLY ---
    function renderTabs() {
        let html = `<button class="${activeCategory === 'All' ? 'active' : ''}" data-filter="All">All</button>`;
        categories.forEach((cat, i) => {
            if (i > 0) html += '<span class="sep">|</span>';
            html += `<span class="sep">|</span>`;
            html += `<button class="${activeCategory === cat ? 'active' : ''}" data-filter="${cat}">${cat}</button>`;
        });
        // Fix: rebuild cleanly
        let parts = [`<button class="${activeCategory === 'All' ? 'active' : ''}" data-filter="All">All</button>`];
        categories.forEach(cat => {
            parts.push(`<span class="sep">|</span>`);
            parts.push(`<button class="${activeCategory === cat ? 'active' : ''}" data-filter="${cat}">${cat}</button>`);
        });
        tabsContainer.innerHTML = parts.join('');

        // Re-attach click listeners
        tabsContainer.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                tabsContainer.querySelector('.active')?.classList.remove('active');
                btn.classList.add('active');
                activeCategory = btn.dataset.filter;
                handleFilter();
            });
        });
    }

    // --- RENDER CARDS ---
    const renderCards = (items) => {
        cardsContainer.innerHTML = '';
        if (items.length === 0) {
            cardsContainer.innerHTML = '<p>No accommodations match your criteria.</p>';
            return;
        }
        items.forEach(item => {
            const card = document.createElement('div');
            card.className = 'card';
            const imgSrc = item.image
                ? (item.image.startsWith('http') ? item.image : '../' + item.image)
                : 'https://placehold.co/250x180/a2b8a1/4F4F4F?text=West+Farm';
            const capacityTag = item.capacity ? `${item.capacity} Pax` : '';
            const tags = [item.category, capacityTag].filter(Boolean);
            card.innerHTML = `
                <img src="${imgSrc}" alt="${item.name}" class="card-img" onerror="this.src='https://placehold.co/250x180/a2b8a1/4F4F4F?text=West+Farm';">
                <div class="card-body">
                    <h3 class="card-title">${item.name}</h3>
                    <p class="card-desc">${item.description || ''}</p>
                    <div class="card-footer">
                        <div class="card-price">₱${item.price.toLocaleString()} <span>/ night</span></div>
                        <div class="card-tags">
                            ${tags.map(tag => `<span class="tag">${tag}</span>`).join('')}
                        </div>
                    </div>
                </div>
            `;
            cardsContainer.appendChild(card);
        });
    };

    const renderCalendar = () => {
        calElement.innerHTML = '';
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        calTitle.textContent = `${currentDate.toLocaleString('default', { month: 'long' })} ${year}`;

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        ['S', 'M', 'T', 'W', 'T', 'F', 'S'].forEach(day => {
            const weekdayEl = document.createElement('div');
            weekdayEl.className = 'cal-weekday';
            weekdayEl.textContent = day;
            calElement.appendChild(weekdayEl);
        });

        for (let i = 0; i < firstDay; i++) {
            const blankDay = document.createElement('div');
            blankDay.className = 'cal-day other-month';
            calElement.appendChild(blankDay);
        }

        for (let i = 1; i <= daysInMonth; i++) {
            const dayEl = document.createElement('div');
            dayEl.className = 'cal-day';
            dayEl.textContent = i;
            const thisDate = new Date(year, month, i);

            if (thisDate < new Date().setHours(0, 0, 0, 0)) {
                dayEl.classList.add('other-month');
            } else {
                dayEl.addEventListener('click', () => handleDateClick(thisDate));
            }

            if (checkInDate && thisDate.getTime() === checkInDate.getTime()) {
                dayEl.classList.add('selected', 'start-range');
            }
            if (checkOutDate && thisDate.getTime() === checkOutDate.getTime()) {
                dayEl.classList.add('selected', 'end-range');
            }
            if (checkInDate && checkOutDate && thisDate > checkInDate && thisDate < checkOutDate) {
                dayEl.classList.add('in-range');
            }

            calElement.appendChild(dayEl);
        }
    };

    // --- HANDLERS ---
    const handleFilter = () => {
        const searchTerm = searchInput.value.toLowerCase();
        const filtered = accommodations.filter(item => {
            const matchesSearch = item.name.toLowerCase().includes(searchTerm) || item.description.toLowerCase().includes(searchTerm);
            const matchesCategory = activeCategory === 'All' || item.category === activeCategory;
            return matchesSearch && matchesCategory;
        });
        renderCards(filtered);
    };

    const handleDateClick = (date) => {
        if (!checkInDate || (checkInDate && checkOutDate)) {
            checkInDate = date;
            checkOutDate = null;
        } else if (date > checkInDate) {
            checkOutDate = date;
        } else {
            checkInDate = date;
        }
        updateBookingSummary();
        renderCalendar();
    };

    const updateBookingSummary = () => {
        const options = { month: 'short', day: 'numeric' };
        checkInEl.textContent = checkInDate ? checkInDate.toLocaleDateString('en-US', options) : '—';
        checkOutEl.textContent = checkOutDate ? checkOutDate.toLocaleDateString('en-US', options) : '—';

        if (checkInDate && checkOutDate) {
            const diffTime = Math.abs(checkOutDate - checkInDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            nightsNumEl.textContent = diffDays;
            bookBtn.disabled = false;
        } else {
            nightsNumEl.textContent = '0';
            bookBtn.disabled = true;
        }
    };

    const updateGuestCount = (type, dir) => {
        if (type === 'adults') {
            adults = Math.max(1, adults + dir);
            adultsEl.textContent = adults;
        } else {
            kids = Math.max(0, kids + dir);
            kidsEl.textContent = kids;
        }
    };

    const showToast = (message) => {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    const updateCopyright = () => {
        if (copyrightInfo) {
            const currentYear = new Date().getFullYear();
            copyrightInfo.textContent = `© ${currentYear}. Angelito, Hazel, Relynne, Raymund All rights reserved.`;
        }
    };

    // --- EVENT LISTENERS ---
    searchInput.addEventListener('input', handleFilter);

    prevMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    nextMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    countersContainer.addEventListener('click', (e) => {
        if (e.target.tagName === 'BUTTON') {
            const type = e.target.dataset.c;
            const dir = parseInt(e.target.dataset.d, 10);
            updateGuestCount(type, dir);
        }
    });

    bookBtn.addEventListener('click', () => {
        if (checkInDate && checkOutDate) {
            const totalGuests = adults + kids;
            showToast(`Booking for ${totalGuests} guest(s) from ${checkInEl.textContent} to ${checkOutEl.textContent}. Integration needed.`);
        } else {
            showToast('Please select check-in and check-out dates.');
        }
    });

    // --- INITIALIZATION ---
    loadFacilities();
    renderCalendar();
    updateBookingSummary();
    updateCopyright();
});
