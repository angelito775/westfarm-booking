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
    let selectedFacility = null;
    let bookedDates = {}; // { facility_id: [{check_in, check_out}, ...] }

    // --- LOGIN STATE ---
    const isLoggedIn = window.__isLoggedIn || false;

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
                    // Restore booking state after facilities are loaded
                    restoreBookingState();
                } else {
                    cardsContainer.innerHTML = '<p>Unable to load accommodations. Please try again later.</p>';
                }
            })
            .catch(() => {
                cardsContainer.innerHTML = '<p>Unable to load accommodations. Please try again later.</p>';
            });
    }

    // --- FETCH BOOKED DATES ---
    function loadBookedDates() {
        fetch('../logic/get_booked_dates.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    bookedDates = data.booked;
                    renderCalendar();
                }
            })
            .catch(() => {
                // Silently fail — calendar still works without booked dates
            });
    }

    // --- RENDER CATEGORY TABS DYNAMICALLY ---
    function renderTabs() {
        let parts = [`<button class="${activeCategory === 'All' ? 'active' : ''}" data-filter="All">All</button>`];
        categories.forEach(cat => {
            parts.push(`<span class="sep">|</span>`);
            parts.push(`<button class="${activeCategory === cat ? 'active' : ''}" data-filter="${cat}">${cat}</button>`);
        });
        tabsContainer.innerHTML = parts.join('');

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
            card.dataset.id = item.id;
            const imgSrc = item.image
                ? (item.image.startsWith('http') ? item.image : '../' + item.image)
                : 'https://placehold.co/250x180/a2b8a1/4F4F4F?text=West+Farm';
            const capacityTag = item.capacity ? `${item.capacity} Pax` : '';
            const tags = [item.category, capacityTag].filter(Boolean);

            card.innerHTML = `
                <div class="select-badge"><i class="fas fa-check"></i></div>
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

            card.addEventListener('click', () => selectFacility(item, card));
            cardsContainer.appendChild(card);
        });

        // Re-highlight if one was already selected
        if (selectedFacility) {
            const prevCard = cardsContainer.querySelector(`.card[data-id="${selectedFacility.id}"]`);
            if (prevCard) {
                prevCard.classList.add('selected');
            }
        }
    };

    // --- FACILITY SELECTION ---
    function selectFacility(item, card) {
        // Deselect previous
        document.querySelectorAll('.card.selected').forEach(c => c.classList.remove('selected'));
        // Select new
        card.classList.add('selected');
        selectedFacility = item;
        // Clear any existing date selection when switching facilities
        checkInDate = null;
        checkOutDate = null;
        updateBookingSidebar();
        updateBookingSummary();
        renderCalendar();
    }

    // --- UPDATE SIDEBAR ---
    function updateBookingSidebar() {
        const facilityBox = document.getElementById('selectedFacilityBox');
        const facilityName = document.getElementById('selectedFacilityName');
        const facilityPrice = document.getElementById('selectedFacilityPrice');
        const totalBox = document.getElementById('bookingTotalBox');
        const totalAmount = document.getElementById('bookingTotalAmount');

        if (selectedFacility) {
            facilityBox.style.display = 'block';
            facilityName.textContent = selectedFacility.name;
            facilityPrice.textContent = `₱${selectedFacility.price.toLocaleString()} / night`;
        } else {
            facilityBox.style.display = 'none';
            totalBox.style.display = 'none';
        }

        updateBookButton();
    }

    // --- HELPERS FOR BOOKED DATE CHECKING ---
    function isDateBooked(date, facilityId) {
        if (!facilityId || !bookedDates[facilityId]) return false;
        const d = date.getTime();
        const ranges = bookedDates[facilityId];
        for (let r = 0; r < ranges.length; r++) {
            const ci = new Date(ranges[r].check_in).setHours(0, 0, 0, 0);
            const co = new Date(ranges[r].check_out).setHours(0, 0, 0, 0);
            // Booked from check_in to day before check_out (check-out day is free)
            if (d >= ci && d < co) return true;
        }
        return false;
    }

    function getBookedPosition(date, facilityId) {
        if (!facilityId || !bookedDates[facilityId]) return '';
        const d = date.getTime();
        const ranges = bookedDates[facilityId];
        for (let r = 0; r < ranges.length; r++) {
            const ci = new Date(ranges[r].check_in).setHours(0, 0, 0, 0);
            const co = new Date(ranges[r].check_out).setHours(0, 0, 0, 0);
            if (d >= ci && d < co) {
                const prevDay = new Date(d - 86400000).setHours(0, 0, 0, 0);
                const nextDay = new Date(d + 86400000).setHours(0, 0, 0, 0);
                const isStart = d === ci;
                const isEnd = nextDay >= co;
                if (isStart && isEnd) return 'booked';
                if (isStart) return 'booked-start';
                if (isEnd) return 'booked-end';
                return 'booked-range';
            }
        }
        return '';
    }

    // --- CALENDAR ---
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

        const fid = selectedFacility ? selectedFacility.id : null;

        for (let i = 1; i <= daysInMonth; i++) {
            const dayEl = document.createElement('div');
            dayEl.className = 'cal-day';
            dayEl.textContent = i;
            const thisDate = new Date(year, month, i);
            const dateOnly = new Date(year, month, i).setHours(0, 0, 0, 0);
            const today = new Date().setHours(0, 0, 0, 0);

            if (dateOnly < today) {
                dayEl.classList.add('other-month');
            } else {
                // Check if this date is booked for the selected facility
                if (fid && isDateBooked(thisDate, fid)) {
                    const pos = getBookedPosition(thisDate, fid);
                    dayEl.classList.add(pos);
                    dayEl.title = 'Already booked';
                } else {
                    dayEl.addEventListener('click', () => handleDateClick(thisDate));
                }
            }

            // User's selected range (only if not overlapping booked)
            if (checkInDate && dateOnly === checkInDate.setHours(0, 0, 0, 0)) {
                dayEl.classList.add('selected', 'start-range');
            }
            if (checkOutDate && dateOnly === checkOutDate.setHours(0, 0, 0, 0)) {
                dayEl.classList.add('selected', 'end-range');
            }
            if (checkInDate && checkOutDate && dateOnly > checkInDate.setHours(0, 0, 0, 0) && dateOnly < checkOutDate.setHours(0, 0, 0, 0)) {
                dayEl.classList.add('in-range');
            }

            calElement.appendChild(dayEl);
        }

        // Add legend
        const existingLegend = calElement.parentElement.querySelector('.cal-legend');
        if (existingLegend) existingLegend.remove();

        const legend = document.createElement('div');
        legend.className = 'cal-legend';
        legend.innerHTML = `
            <div class="cal-legend-item"><span class="cal-legend-dot available"></span> Available</div>
            <div class="cal-legend-item"><span class="cal-legend-dot selected"></span> Selected</div>
            <div class="cal-legend-item"><span class="cal-legend-dot booked"></span> Booked</div>
        `;
        calElement.parentElement.appendChild(legend);
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

    // Check if any date in the range [from, to] is booked
    function isRangeBooked(from, to, facilityId) {
        if (!facilityId || !bookedDates[facilityId]) return false;
        const d = new Date(from);
        d.setHours(0, 0, 0, 0);
        const endTime = new Date(to).setHours(0, 0, 0, 0);
        while (d.getTime() <= endTime) {
            if (isDateBooked(d, facilityId)) return true;
            d.setDate(d.getDate() + 1);
        }
        return false;
    }

    const handleDateClick = (date) => {
        // If no facility selected, prompt user
        if (!selectedFacility) {
            showToast('Please select a facility first.');
            return;
        }

        const fid = selectedFacility.id;

        // Check if the clicked date itself is booked
        if (isDateBooked(date, fid)) {
            showToast('This date is already booked.');
            return;
        }

        if (!checkInDate || (checkInDate && checkOutDate)) {
            // Starting a new selection
            checkInDate = new Date(date);
            checkOutDate = null;
        } else {
            // Allow same-day checkout for Cottage, Pool, Event Hall; others need different dates
            const sameDayCategories = ['Cottage', 'Pool', 'Event Hall'];
            const allowSameDay = sameDayCategories.includes(selectedFacility.category);
            const isValidCheckout = allowSameDay ? date >= checkInDate : date > checkInDate;
            
            if (isValidCheckout) {
                // Selecting check-out — verify the range doesn't include booked dates
                if (isRangeBooked(checkInDate, date, fid)) {
                    showToast('Your selected range includes already-booked dates. Please choose different dates.');
                    checkInDate = new Date(date);
                    checkOutDate = null;
                    updateBookingSummary();
                    renderCalendar();
                    return;
                }
                checkOutDate = new Date(date);
            } else {
                // Clicked before or on check-in, restart
                checkInDate = new Date(date);
                checkOutDate = null;
            }
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
        } else {
            nightsNumEl.textContent = '0';
        }

        updateTotal();
        updateBookButton();
    };

    function updateTotal() {
        const totalBox = document.getElementById('bookingTotalBox');
        const totalAmount = document.getElementById('bookingTotalAmount');

        if (selectedFacility && checkInDate && checkOutDate) {
            const diffTime = Math.abs(checkOutDate - checkInDate);
            const nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            if (nights > 0) {
                const total = selectedFacility.price * nights;
                totalAmount.textContent = `₱${total.toLocaleString()}`;
                totalBox.style.display = 'block';
            } else {
                totalBox.style.display = 'none';
            }
        } else {
            totalBox.style.display = 'none';
        }
    }

    function updateBookButton() {
        if (!isLoggedIn) {
            bookBtn.textContent = 'Sign in to Book';
            bookBtn.disabled = false;
        } else if (!selectedFacility) {
            bookBtn.textContent = 'Select a facility';
            bookBtn.disabled = true;
        } else if (!checkInDate || !checkOutDate) {
            bookBtn.textContent = 'Select dates';
            bookBtn.disabled = true;
        } else {
            bookBtn.textContent = 'Book Now';
            bookBtn.disabled = false;
        }
    }

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

    // Helper to format date as YYYY-MM-DD in local timezone (no UTC conversion)
    function formatDateLocal(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // --- BOOK NOW BUTTON ---
    bookBtn.addEventListener('click', () => {
        if (!isLoggedIn) {
            openAuthModal();
            return;
        }
        if (!selectedFacility) {
            showToast('Please select a facility first.');
            return;
        }
        if (!checkInDate || !checkOutDate) {
            showToast('Please select check-in and check-out dates.');
            return;
        }

        // Show confirmation modal
        const totalGuests = adults + kids;
        const diffTime = Math.abs(checkOutDate - checkInDate);
        const nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        const total = selectedFacility.price * nights;

        const summaryHtml = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:14px;">
                <div style="color:#6b7280;">Facility:</div><div style="font-weight:600;color:#2F3D2E;">${selectedFacility.name}</div>
                <div style="color:#6b7280;">Check-in:</div><div style="font-weight:600;">${checkInDate.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })}</div>
                <div style="color:#6b7280;">Check-out:</div><div style="font-weight:600;">${checkOutDate.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })}</div>
                <div style="color:#6b7280;">Nights:</div><div style="font-weight:600;">${nights}</div>
                <div style="color:#6b7280;">Guests:</div><div style="font-weight:600;">${totalGuests} (${adults} adults, ${kids} kids)</div>
                <div style="color:#6b7280;">Total:</div><div style="font-weight:700;font-size:18px;color:#1a3a1a;">₱${total.toLocaleString()}</div>
            </div>
        `;

        document.getElementById('confirmBookingSummary').innerHTML = summaryHtml;
        document.getElementById('confirmFacilityId').value = selectedFacility.id;
        document.getElementById('confirmCheckIn').value = formatDateLocal(checkInDate);
        document.getElementById('confirmCheckOut').value = formatDateLocal(checkOutDate);
        document.getElementById('confirmNumGuests').value = totalGuests;

        openConfirmBookingModal();
    });

    // ═══════════════════════════════════════════
    // ── AUTH MODAL FUNCTIONS ──
    // ═══════════════════════════════════════════
    window.openAuthModal = function() {
        document.getElementById('authModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    window.closeAuthModal = function() {
        document.getElementById('authModal').style.display = 'none';
        document.body.style.overflow = '';
    };

    window.switchAuthTab = function(tab) {
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const loginTab = document.getElementById('loginTabBtn');
        const registerTab = document.getElementById('registerTabBtn');

        if (tab === 'login') {
            loginForm.style.display = 'block';
            registerForm.style.display = 'none';
            loginTab.classList.add('active');
            registerTab.classList.remove('active');
        } else {
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
            loginTab.classList.remove('active');
            registerTab.classList.add('active');
        }
        // Clear errors
        document.getElementById('loginError').style.display = 'none';
        document.getElementById('registerError').style.display = 'none';
    };

    window.toggleLoginPw = function() {
        const pwInput = document.getElementById('loginPassword');
        const pwIcon = document.getElementById('loginPwIcon');
        if (pwInput.type === 'password') {
            pwInput.type = 'text';
            pwIcon.classList.remove('fa-eye');
            pwIcon.classList.add('fa-eye-slash');
        } else {
            pwInput.type = 'password';
            pwIcon.classList.remove('fa-eye-slash');
            pwIcon.classList.add('fa-eye');
        }
    };

    // Save current booking state to sessionStorage before reload
    function saveBookingState() {
        const state = {
            facilityId: selectedFacility ? selectedFacility.id : null,
            checkIn: checkInDate ? checkInDate.toISOString() : null,
            checkOut: checkOutDate ? checkOutDate.toISOString() : null,
            adults: adults,
            kids: kids
        };
        sessionStorage.setItem('bookingState', JSON.stringify(state));
    }

    // Restore booking state from sessionStorage after reload
    function restoreBookingState() {
        const saved = sessionStorage.getItem('bookingState');
        if (!saved) return;
        try {
            const state = JSON.parse(saved);
            if (state.checkIn) checkInDate = new Date(state.checkIn);
            if (state.checkOut) checkOutDate = new Date(state.checkOut);
            if (state.adults) adults = state.adults;
            if (state.kids) kids = state.kids;

            // Restore selected facility from loaded accommodations
            if (state.facilityId && accommodations.length) {
                const found = accommodations.find(a => a.id === state.facilityId);
                if (found) selectedFacility = found;
            }

            // Update UI
            updateBookingSummary();
            renderCalendar();
            updateBookingSidebar();
            updateGuestCountDisplay();

            // Re-highlight selected card after render
            if (selectedFacility) {
                setTimeout(() => {
                    const card = document.querySelector(`.card[data-id="${selectedFacility.id}"]`);
                    if (card) card.classList.add('selected');
                }, 100);
            }

            sessionStorage.removeItem('bookingState');
        } catch (e) {
            sessionStorage.removeItem('bookingState');
        }
    }

    function updateGuestCountDisplay() {
        adultsEl.textContent = adults;
        kidsEl.textContent = kids;
    }

    // ── PER-FIELD VALIDATION HELPERS ──
    function showFieldError(inputEl, errorEl, msg) {
        inputEl.classList.add('input-error');
        errorEl.textContent = msg;
        errorEl.classList.add('show');
    }
    function clearFieldError(inputEl, errorEl) {
        inputEl.classList.remove('input-error');
        errorEl.textContent = '';
        errorEl.classList.remove('show');
    }
    function clearAllFieldErrors(formId) {
        document.querySelectorAll('#' + formId + ' .auth-field-error').forEach(el => el.classList.remove('show'));
        document.querySelectorAll('#' + formId + ' input').forEach(el => el.classList.remove('input-error'));
    }

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    function validatePhone(phone) {
        // Philippine: 11 digits starting with 09
        return /^09\d{9}$/.test(phone.replace(/[\s-]/g, ''));
    }

    // Real-time clearing on input
    document.getElementById('loginEmail').addEventListener('input', function() {
        if (this.value.trim()) clearFieldError(this, document.getElementById('loginEmailError'));
    });
    document.getElementById('loginPassword').addEventListener('input', function() {
        if (this.value) clearFieldError(this, document.getElementById('loginPasswordError'));
    });
    ['regFirstName','regLastName','regEmail','regPhone','regPassword','regConfirmPassword'].forEach(function(id) {
        document.getElementById(id).addEventListener('input', function() {
            clearFieldError(this, document.getElementById(id + 'Error'));
        });
    });
    // Only allow digits in phone field
    document.getElementById('regPhone').addEventListener('input', function() {
        this.value = this.value.replace(/[^\d]/g, '').slice(0, 11);
    });

    window.handleLoginSubmit = function(e) {
        e.preventDefault();
        clearAllFieldErrors('loginForm');

        const btn = document.getElementById('loginSubmitBtn');
        const errorDiv = document.getElementById('loginError');
        const email = document.getElementById('loginEmail').value.trim();
        const password = document.getElementById('loginPassword').value;
        let valid = true;

        if (!email) {
            showFieldError(document.getElementById('loginEmail'), document.getElementById('loginEmailError'), 'Email address is required.');
            valid = false;
        } else if (!validateEmail(email)) {
            showFieldError(document.getElementById('loginEmail'), document.getElementById('loginEmailError'), 'Please enter a valid email address.');
            valid = false;
        }

        if (!password) {
            showFieldError(document.getElementById('loginPassword'), document.getElementById('loginPasswordError'), 'Password is required.');
            valid = false;
        } else if (password.length < 6) {
            showFieldError(document.getElementById('loginPassword'), document.getElementById('loginPasswordError'), 'Password must be at least 6 characters.');
            valid = false;
        }

        if (!valid) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
        errorDiv.style.display = 'none';

        const formData = new FormData();
        formData.append('ajax_login', '1');
        formData.append('email', email);
        formData.append('password', password);

        fetch('../logic/ajax_auth_process.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                saveBookingState();
                window.location.href = '../public/booking.php';
            } else {
                errorDiv.textContent = data.message || 'Login failed. Please try again.';
                errorDiv.style.display = 'block';
            }
        })
        .catch(() => {
            errorDiv.textContent = 'Network error. Please try again.';
            errorDiv.style.display = 'block';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
        });
    };

    window.handleRegisterSubmit = function(e) {
        e.preventDefault();
        clearAllFieldErrors('registerForm');

        const btn = document.getElementById('registerSubmitBtn');
        const errorDiv = document.getElementById('registerError');
        const firstName = document.getElementById('regFirstName').value.trim();
        const lastName = document.getElementById('regLastName').value.trim();
        const email = document.getElementById('regEmail').value.trim();
        const phone = document.getElementById('regPhone').value.trim();
        const password = document.getElementById('regPassword').value;
        const confirmPassword = document.getElementById('regConfirmPassword').value;
        let valid = true;

        // First Name
        if (!firstName) {
            showFieldError(document.getElementById('regFirstName'), document.getElementById('regFirstNameError'), 'First name is required.');
            valid = false;
        } else if (firstName.length < 2) {
            showFieldError(document.getElementById('regFirstName'), document.getElementById('regFirstNameError'), 'Must be at least 2 characters.');
            valid = false;
        } else if (!/^[a-zA-Z\s'-]+$/.test(firstName)) {
            showFieldError(document.getElementById('regFirstName'), document.getElementById('regFirstNameError'), 'Only letters, spaces, hyphens allowed.');
            valid = false;
        }

        // Last Name
        if (!lastName) {
            showFieldError(document.getElementById('regLastName'), document.getElementById('regLastNameError'), 'Last name is required.');
            valid = false;
        } else if (lastName.length < 2) {
            showFieldError(document.getElementById('regLastName'), document.getElementById('regLastNameError'), 'Must be at least 2 characters.');
            valid = false;
        } else if (!/^[a-zA-Z\s'-]+$/.test(lastName)) {
            showFieldError(document.getElementById('regLastName'), document.getElementById('regLastNameError'), 'Only letters, spaces, hyphens allowed.');
            valid = false;
        }

        // Email
        if (!email) {
            showFieldError(document.getElementById('regEmail'), document.getElementById('regEmailError'), 'Email address is required.');
            valid = false;
        } else if (!validateEmail(email)) {
            showFieldError(document.getElementById('regEmail'), document.getElementById('regEmailError'), 'Please enter a valid email address (e.g. you@example.com).');
            valid = false;
        }

        // Phone (optional but validated if provided)
        if (phone && !validatePhone(phone)) {
            showFieldError(document.getElementById('regPhone'), document.getElementById('regPhoneError'), 'Philippine mobile: 11 digits starting with 09 (e.g. 09123456789).');
            valid = false;
        }

        // Password
        if (!password) {
            showFieldError(document.getElementById('regPassword'), document.getElementById('regPasswordError'), 'Password is required.');
            valid = false;
        } else if (password.length < 6) {
            showFieldError(document.getElementById('regPassword'), document.getElementById('regPasswordError'), 'Password must be at least 6 characters.');
            valid = false;
        }

        // Confirm Password
        if (!confirmPassword) {
            showFieldError(document.getElementById('regConfirmPassword'), document.getElementById('regConfirmPasswordError'), 'Please confirm your password.');
            valid = false;
        } else if (password && confirmPassword !== password) {
            showFieldError(document.getElementById('regConfirmPassword'), document.getElementById('regConfirmPasswordError'), 'Passwords do not match.');
            valid = false;
        }

        if (!valid) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating account...';
        errorDiv.style.display = 'none';

        const formData = new FormData();
        formData.append('first_name', firstName);
        formData.append('last_name', lastName);
        formData.append('email', email);
        formData.append('phone_number', phone);
        formData.append('password', password);
        formData.append('confirm_password', confirmPassword);

        fetch('../logic/ajax_register_process.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                saveBookingState();
                window.location.href = '../public/booking.php';
            } else {
                errorDiv.textContent = data.message || 'Registration failed. Please try again.';
                errorDiv.style.display = 'block';
            }
        })
        .catch(() => {
            errorDiv.textContent = 'Network error. Please try again.';
            errorDiv.style.display = 'block';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
        });
    };

    // Close auth modal on overlay click
    document.getElementById('authModal').addEventListener('click', function(e) {
        if (e.target === this) closeAuthModal();
    });

    // ═══════════════════════════════════════════
    // ── CONFIRM BOOKING MODAL FUNCTIONS ──
    // ═══════════════════════════════════════════
    window.openConfirmBookingModal = function() {
        document.getElementById('confirmBookingModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    window.closeConfirmBookingModal = function() {
        document.getElementById('confirmBookingModal').style.display = 'none';
        document.body.style.overflow = '';
    };

    document.getElementById('confirmBookingModal').addEventListener('click', function(e) {
        if (e.target === this) closeConfirmBookingModal();
    });

    // ═══════════════════════════════════════════
    // ── SUCCESS / ERROR HANDLING ──
    // ═══════════════════════════════════════════
    window.closeSuccessModal = function() {
        document.getElementById('bookingSuccessModal').style.display = 'none';
        document.body.style.overflow = '';
    };

    // Check for booking success/error from server redirect
    if (window.__bookingSuccess) {
        document.getElementById('bookingSuccessModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    if (window.__bookingError) {
        const errMap = {
            'not_logged_in': 'Please sign in to make a booking.',
            'invalid_facility': 'Please select a valid facility.',
            'empty_dates': 'Please select check-in and check-out dates.',
            'invalid_dates': 'Check-out date must be after check-in date.',
            'facility_unavailable': 'This facility is already booked for the selected dates.',
            'booking_failed': 'Unable to create booking. Please try again.',
        };
        showToast(errMap[window.__bookingError] || 'An error occurred. Please try again.');
    }

    // ═══════════════════════════════════════════
    // ── EVENT LISTENERS ──
    // ═══════════════════════════════════════════
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

    // Sign in button in sidebar
    const loginToBookBtn = document.getElementById('loginToBookBtn');
    if (loginToBookBtn) {
        loginToBookBtn.addEventListener('click', () => openAuthModal());
    }

    // --- INITIALIZATION ---
    loadFacilities();
    loadBookedDates();
    renderCalendar();
    updateBookingSummary();
    updateCopyright();
});
