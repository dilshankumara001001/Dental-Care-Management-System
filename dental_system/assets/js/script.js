/* ============================================================
   DENTAL SYSTEM — JS v3.0
   Beautiful Interactions + Animations
============================================================ */

// ============================================================
// TOAST NOTIFICATIONS
// ============================================================
function showToast(message, type = 'info', duration = 4000) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    const icons = {
        success: 'check-circle-fill',
        danger:  'x-circle-fill',
        warning: 'exclamation-triangle-fill',
        info:    'info-circle-fill'
    };
    
    const toast = document.createElement('div');
    toast.className = `toast-msg toast-${type}`;
    toast.innerHTML = `
        <i class="bi bi-${icons[type] || icons.info}"></i>
        <div class="flex-grow-1">${message}</div>
        <button class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Convert PHP alerts to toasts
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.alert').forEach(alert => {
        let type = 'info';
        if (alert.classList.contains('alert-success')) type = 'success';
        if (alert.classList.contains('alert-danger'))  type = 'danger';
        if (alert.classList.contains('alert-warning')) type = 'warning';
        showToast(alert.innerHTML.trim(), type);
        alert.remove();
    });
});

// ============================================================
// DARK MODE
// ============================================================
function initDarkMode() {
    const isDark = localStorage.getItem('darkMode') === 'true';
    if (isDark) document.body.classList.add('dark-mode');
    
    const btn = document.createElement('button');
    btn.className = 'dark-toggle';
    btn.innerHTML = isDark ? '<i class="bi bi-sun-fill"></i>' : '<i class="bi bi-moon-stars-fill"></i>';
    btn.title = 'Toggle Dark Mode (Ctrl+Shift+D)';
    btn.onclick = function() {
        document.body.classList.toggle('dark-mode');
        const dark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', dark);
        this.innerHTML = dark ? '<i class="bi bi-sun-fill"></i>' : '<i class="bi bi-moon-stars-fill"></i>';
        showToast(dark ? '🌙 Dark mode enabled' : '☀️ Light mode enabled', 'info', 2000);
    };
    document.body.appendChild(btn);
}

// ============================================================
// MOBILE MENU
// ============================================================
function initMobileMenu() {
    const menuBtn = document.createElement('button');
    menuBtn.className = 'mobile-menu-btn';
    menuBtn.innerHTML = '<i class="bi bi-list" style="font-size:1.5rem;"></i>';
    document.body.appendChild(menuBtn);
    
    const backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);
    
    const sidebar = document.getElementById('sidebar-wrapper');
    
    menuBtn.onclick = () => {
        sidebar.classList.toggle('mobile-open');
        backdrop.classList.toggle('active');
    };
    
    backdrop.onclick = () => {
        sidebar.classList.remove('mobile-open');
        backdrop.classList.remove('active');
    };
}

// ============================================================
// ANIMATED COUNTERS
// ============================================================
function animateCounters() {
    document.querySelectorAll('h1, h2, h3, h4').forEach(el => {
        const text = el.innerText.trim();
        // Match numbers (with commas and decimals)
        const match = text.match(/^([^0-9]*)([0-9,]+(?:\.[0-9]+)?)([^0-9]*)$/);
        if (!match) return;
        if (el.dataset.animated) return;
        if (text.length > 20) return;
        
        const prefix = match[1];
        const numStr = match[2].replace(/,/g, '');
        const num = parseFloat(numStr);
        const suffix = match[3];
        if (isNaN(num) || num === 0) return;
        
        el.dataset.animated = 'true';
        const decimals = numStr.split('.')[1]?.length || 0;
        const hasComma = match[2].includes(',');
        
        const duration = 1500;
        const startTime = performance.now();
        
        function update(now) {
            const progress = Math.min((now - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 4); // ease-out-quart
            const current = num * eased;
            const display = hasComma 
                ? current.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })
                : current.toFixed(decimals);
            el.innerText = prefix + display + suffix;
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    });
}

// ============================================================
// SPINNER
// ============================================================
function showSpinner() {
    let spinner = document.querySelector('.spinner-overlay');
    if (!spinner) {
        spinner = document.createElement('div');
        spinner.className = 'spinner-overlay';
        spinner.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(spinner);
    }
    spinner.classList.add('active');
}

function hideSpinner() {
    const spinner = document.querySelector('.spinner-overlay');
    if (spinner) spinner.classList.remove('active');
}

// ============================================================
// PAGE LOAD INITIALIZATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Apply stagger to cards
    document.querySelectorAll('.card').forEach((card, i) => {
        if (!card.style.animationDelay) {
            card.style.animationDelay = `${i * 0.05}s`;
        }
    });
    
    // Ripple effect on buttons
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const ripple = document.createElement('span');
            const size = Math.max(rect.width, rect.height);
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255,255,255,0.6);
                transform: scale(0);
                animation: ripple 0.6s linear;
                left: ${e.clientX - rect.left - size/2}px;
                top: ${e.clientY - rect.top - size/2}px;
                width: ${size}px; height: ${size}px;
                pointer-events: none;
            `;
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });
    
    // Initialize
    initDarkMode();
    initMobileMenu();
    setTimeout(animateCounters, 300);
    
    // Page fade-in
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.4s';
    requestAnimationFrame(() => {
        document.body.style.opacity = '1';
    });
    
    // Confetti on success
    if (window.location.search.includes('success=1')) {
        fireConfetti();
    }
});

// ============================================================
// FORM SUBMIT LOADING
// ============================================================
document.addEventListener('submit', function(e) {
    const btn = e.target.querySelector('button[type="submit"], button[name]');
    if (btn && !btn.dataset.noSpinner) {
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = original;
        }, 5000);
    }
});

// ============================================================
// CONFETTI
// ============================================================
function fireConfetti() {
    let canvas = document.getElementById('confetti-canvas');
    if (!canvas) {
        canvas = document.createElement('canvas');
        canvas.id = 'confetti-canvas';
        document.body.appendChild(canvas);
    }
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    
    const ctx = canvas.getContext('2d');
    const particles = [];
    const colors = ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe', '#ffc107', '#28a745', '#dc3545'];
    
    for (let i = 0; i < 180; i++) {
        particles.push({
            x: Math.random() * canvas.width,
            y: -20 - Math.random() * 200,
            size: 6 + Math.random() * 10,
            speedY: 2 + Math.random() * 5,
            speedX: -3 + Math.random() * 6,
            color: colors[Math.floor(Math.random() * colors.length)],
            rotation: Math.random() * 360,
            rotSpeed: -12 + Math.random() * 24,
            shape: Math.random() > 0.5 ? 'rect' : 'circle'
        });
    }
    
    let frames = 0;
    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particles.forEach(p => {
            p.y += p.speedY;
            p.x += p.speedX;
            p.rotation += p.rotSpeed;
            
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rotation * Math.PI / 180);
            ctx.fillStyle = p.color;
            ctx.globalAlpha = Math.max(0, 1 - frames / 200);
            if (p.shape === 'rect') {
                ctx.fillRect(-p.size/2, -p.size/2, p.size, p.size * 0.6);
            } else {
                ctx.beginPath();
                ctx.arc(0, 0, p.size/2, 0, Math.PI * 2);
                ctx.fill();
            }
            ctx.restore();
        });
        frames++;
        if (frames < 220) requestAnimationFrame(animate);
        else ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
    animate();
}

// ============================================================
// CUSTOM CONFIRM MODAL
// ============================================================
function customConfirm(message, onConfirm, options = {}) {
    const { title = 'Are you sure?', confirmText = 'Yes, Delete', cancelText = 'Cancel', type = 'danger' } = options;
    
    const overlay = document.createElement('div');
    overlay.className = 'custom-confirm-overlay';
    
    const icons = {
        danger:  'exclamation-triangle-fill',
        warning: 'exclamation-circle-fill',
        success: 'check-circle-fill',
        info:    'info-circle-fill'
    };
    
    overlay.innerHTML = `
        <div class="custom-confirm">
            <div class="confirm-icon text-${type}">
                <i class="bi bi-${icons[type]}"></i>
            </div>
            <h4>${title}</h4>
            <p>${message}</p>
            <div class="confirm-actions">
                <button class="btn btn-secondary cancel-btn">${cancelText}</button>
                <button class="btn btn-${type} confirm-btn">${confirmText}</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
    setTimeout(() => overlay.classList.add('active'), 10);
    
    overlay.querySelector('.cancel-btn').onclick = () => close(false);
    overlay.querySelector('.confirm-btn').onclick = () => close(true);
    overlay.onclick = (e) => { if (e.target === overlay) close(false); };
    
    function close(result) {
        overlay.classList.remove('active');
        setTimeout(() => overlay.remove(), 300);
        if (result) onConfirm();
    }
}

// Attach to delete links
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('a[href*="delete="], button[data-delete]').forEach(el => {
        const href = el.getAttribute('href') || el.dataset.delete;
        const name = el.dataset.name || el.closest('tr')?.querySelector('b')?.innerText?.trim() || 'this item';
        
        el.onclick = function(e) {
            e.preventDefault();
            customConfirm(
                `Delete <b>${name}</b>? This action cannot be undone.`,
                () => { window.location.href = href; },
                { title: 'Confirm Delete', confirmText: '<i class="bi bi-trash"></i> Delete', type: 'danger' }
            );
            return false;
        };
    });
});

window.confirmDelete = () => true; // fallback

// ============================================================
// THEME COLOR PICKER
// ============================================================
function initThemePicker() {
    const themes = {
        purple: { primary: '#667eea', secondary: '#764ba2', dark: '#5568d3' },
        blue:   { primary: '#2196f3', secondary: '#00bcd4', dark: '#1976d2' },
        green:  { primary: '#28a745', secondary: '#20c997', dark: '#1e7e34' },
        orange: { primary: '#ff9800', secondary: '#f44336', dark: '#e68900' },
        pink:   { primary: '#ec4899', secondary: '#8b5cf6', dark: '#db2777' }
    };
    
    const saved = localStorage.getItem('themeColor') || 'purple';
    applyTheme(saved);
    
    const btn = document.createElement('button');
    btn.className = 'theme-picker';
    btn.innerHTML = '<i class="bi bi-palette-fill"></i>';
    btn.title = 'Change Theme Color';
    document.body.appendChild(btn);
    
    const panel = document.createElement('div');
    panel.className = 'theme-panel';
    panel.innerHTML = `
        <h6><i class="bi bi-brush-fill"></i> Theme Color</h6>
        <div class="theme-colors">
            ${Object.keys(themes).map(name => 
                `<div class="theme-color color-${name} ${name === saved ? 'active' : ''}" data-theme="${name}" title="${name}"></div>`
            ).join('')}
        </div>
    `;
    document.body.appendChild(panel);
    
    btn.onclick = (e) => {
        e.stopPropagation();
        panel.classList.toggle('active');
    };
    
    panel.querySelectorAll('.theme-color').forEach(el => {
        el.onclick = () => {
            const name = el.dataset.theme;
            applyTheme(name);
            localStorage.setItem('themeColor', name);
            panel.querySelectorAll('.theme-color').forEach(x => x.classList.remove('active'));
            el.classList.add('active');
            showToast(`🎨 Theme changed to <b>${name}</b>!`, 'success', 2000);
        };
    });
    
    document.addEventListener('click', (e) => {
        if (!panel.contains(e.target) && !btn.contains(e.target)) {
            panel.classList.remove('active');
        }
    });
}

function applyTheme(name) {
    const themes = {
        purple: { primary: '#667eea', secondary: '#764ba2', dark: '#5568d3' },
        blue:   { primary: '#2196f3', secondary: '#00bcd4', dark: '#1976d2' },
        green:  { primary: '#28a745', secondary: '#20c997', dark: '#1e7e34' },
        orange: { primary: '#ff9800', secondary: '#f44336', dark: '#e68900' },
        pink:   { primary: '#ec4899', secondary: '#8b5cf6', dark: '#db2777' }
    };
    const t = themes[name] || themes.purple;
    document.documentElement.style.setProperty('--primary', t.primary);
    document.documentElement.style.setProperty('--primary-dark', t.dark);
    document.documentElement.style.setProperty('--secondary', t.secondary);
    document.documentElement.style.setProperty('--gradient-primary', 
        `linear-gradient(135deg, ${t.primary} 0%, ${t.secondary} 100%)`);
}

// ============================================================
// KEYBOARD SHORTCUTS
// ============================================================
document.addEventListener('keydown', function(e) {
    // Ctrl+K - Focus search
    if (e.ctrlKey && e.key === 'k') {
        e.preventDefault();
        document.querySelector('input[name="search"], input[type="search"]')?.focus();
    }
    // Ctrl+Shift+D - Dark mode
    if (e.ctrlKey && e.shiftKey && e.key === 'D') {
        e.preventDefault();
        document.querySelector('.dark-toggle')?.click();
    }
    // Escape - Close modals
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.show').forEach(m => {
            bootstrap.Modal.getInstance(m)?.hide();
        });
    }
});

// ============================================================
// ACTIVE SIDEBAR LINK
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    document.querySelectorAll('#sidebar-wrapper .list-group-item').forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath === new URL(href, location.origin).pathname) {
            link.classList.add('active');
        }
    });
});

// ============================================================
// INIT ALL
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    initThemePicker();
});

// ============================================================
// LOG
// ============================================================
console.log('%c🦷 Dental Care System', 'background: linear-gradient(135deg, #667eea, #764ba2); color:white; font-size:20px; padding:10px 20px; border-radius:10px; font-weight:bold;');
console.log('%c✨ Beautiful UI v3.0 loaded', 'color:#667eea; font-size:14px; font-weight:bold;');
console.log('%c⌨️  Shortcuts: Ctrl+K (search) | Ctrl+Shift+D (dark mode) | Esc (close modal)', 'color:#6c757d; font-size:12px;');