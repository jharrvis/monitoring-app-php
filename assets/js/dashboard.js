// Dashboard JavaScript
let currentPage = 1;
let autoSlideInterval = null;
let logoClickCount = 0;
let logoClickTimer = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateTime();
    setInterval(updateTime, 1000);

    // Start animations
    animateCounters();
    animateProgressBars();

    // Start auto-slide
    startAutoSlide();
});

// Update current time
function updateTime() {
    const now = new Date();
    const timeStr = now.toLocaleString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    const timeElement = document.getElementById('currentTime');
    if (timeElement) {
        timeElement.textContent = timeStr;
    }
}

// Animate counters
function animateCounters() {
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const target = parseFloat(counter.getAttribute('data-target'));
        const duration = 1000; // 1 second
        const increment = target / (duration / 16); // 60fps
        let current = 0;

        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = Math.floor(target);
            }
        };

        updateCounter();
    });
}

// Animate progress bars
function animateProgressBars() {
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const targetWidth = bar.getAttribute('data-width');
        setTimeout(() => {
            bar.style.width = targetWidth;
        }, 300);
    });

    const percentages = document.querySelectorAll('.percentage');
    percentages.forEach(pct => {
        const target = parseFloat(pct.getAttribute('data-target'));
        const duration = 1000;
        const increment = target / (duration / 16);
        let current = 0;

        const updatePercentage = () => {
            current += increment;
            if (current < target) {
                pct.textContent = current.toFixed(1) + '%';
                requestAnimationFrame(updatePercentage);
            } else {
                pct.textContent = target.toFixed(1) + '%';
            }
        };

        updatePercentage();
    });
}

// Auto-slide between pages
function startAutoSlide() {
    const slideDuration = 5000; // 5 seconds

    autoSlideInterval = setInterval(() => {
        switchPage(currentPage === 1 ? 2 : 1);
    }, slideDuration);
}

function switchPage(pageNumber) {
    document.querySelectorAll('.slide-page').forEach(page => {
        page.classList.remove('active');
    });

    document.getElementById('page' + pageNumber).classList.add('active');
    currentPage = pageNumber;

    // Re-animate on page switch
    setTimeout(() => {
        animateCounters();
        animateProgressBars();
    }, 100);
}

// Filter by status
function filterStatus(status) {
    const cards = document.querySelectorAll('.monitoring-card');

    cards.forEach(card => {
        if (status === 'all' || card.getAttribute('data-status') === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });

    // Update button styles
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('ring-2', 'ring-offset-2');
    });
    event.target.closest('button').classList.add('ring-2', 'ring-offset-2');
}

// Logo click handler (5 clicks to admin)
function handleLogoClick() {
    logoClickCount++;

    // Show counter badge
    let counter = document.querySelector('.click-counter');
    if (!counter) {
        counter = document.createElement('div');
        counter.className = 'click-counter';
        document.getElementById('logoContainer').appendChild(counter);
    }
    counter.textContent = logoClickCount;

    // Clear previous timer
    if (logoClickTimer) {
        clearTimeout(logoClickTimer);
    }

    // Reset after 2 seconds
    logoClickTimer = setTimeout(() => {
        logoClickCount = 0;
        if (counter) {
            counter.remove();
        }
    }, 2000);

    // Redirect to admin after 5 clicks
    if (logoClickCount >= 5) {
        window.location.href = 'admin/login.php';
    }
}

// Show detail modal
function showDetail(config, data) {
    const modal = document.getElementById('detailModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');

    modalTitle.textContent = config.monitoring_name;

    // Build modal content
    let statusClass = 'text-green-600';
    let statusText = 'Good';

    if (data.status === 'warning') {
        statusClass = 'text-yellow-600';
        statusText = 'Peringatan';
    } else if (data.status === 'critical') {
        statusClass = 'text-red-600';
        statusText = 'Kritis';
    }

    modalContent.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Deskripsi</h3>
                <p class="text-gray-800">${config.monitoring_description}</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Status</h3>
                <p class="text-2xl font-bold ${statusClass}">${statusText}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">Nilai Saat Ini</p>
                <p class="text-2xl font-bold text-blue-600">${data.current_value} ${config.unit}</p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">Target</p>
                <p class="text-2xl font-bold text-green-600">${data.target_value} ${config.unit}</p>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">Persentase</p>
                <p class="text-2xl font-bold text-purple-600">${data.percentage}%</p>
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-sm font-semibold text-gray-600 mb-3">Tren Historis</h3>
            <canvas id="detailChart" height="80"></canvas>
        </div>
    `;

    modal.classList.add('show');

    // Load chart data
    loadDetailChart(config.id);

    // Stop auto-slide when modal open
    if (autoSlideInterval) {
        clearInterval(autoSlideInterval);
    }
}

function closeModal() {
    const modal = document.getElementById('detailModal');
    modal.classList.remove('show');

    // Resume auto-slide
    startAutoSlide();
}

// Load detail chart
async function loadDetailChart(monitoringId) {
    try {
        const response = await fetch(`api/monitoring-data/index.php?monitoring_id=${monitoringId}`);
        const result = await response.json();

        if (result.data) {
            const data = result.data.slice(0, 8).reverse(); // Last 8 quarters

            const labels = data.map(d => `${d.year} Q${d.quarter}`);
            const percentages = data.map(d => d.percentage);

            const ctx = document.getElementById('detailChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Persentase (%)',
                        data: percentages,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Failed to load chart:', error);
    }
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('detailModal');
    if (event.target === modal) {
        closeModal();
    }
}
