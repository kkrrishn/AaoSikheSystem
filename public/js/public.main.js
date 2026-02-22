// Counter Animation
function animateCounter(id, start, end, duration) {
    let obj = document.getElementById(id);
    let range = end - start;
    let increment = end > start ? 1 : -1;
    let step = Math.abs(Math.floor(duration / range));
    let timer = setInterval(() => {
        start += increment;
        obj.textContent = start.toLocaleString();
        if (start == end) {
            clearInterval(timer);
        }
    }, step);
}

// Testimonial Slider
let currentTestimonialSlide = 0;
const testimonialSlides = document.querySelectorAll('.testimonial-card').length;
const testimonialTrack = document.querySelector('.testimonial-track');
const testimonialDots = document.querySelectorAll('.testimonial-slider .slider-dot');

function goToTestimonialSlide(slideIndex) {
    currentTestimonialSlide = slideIndex;
    updateTestimonialSlider();
}

function updateTestimonialSlider() {
    if (!testimonialTrack) return;
    
    const slideWidth = document.querySelector('.testimonial-card').offsetWidth + 30; // 30px for margin
    testimonialTrack.style.transform = `translateX(-${currentTestimonialSlide * slideWidth}px)`;
    
    // Update dots
    if (testimonialDots.length > 0) {
        testimonialDots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentTestimonialSlide);
        });
    }
}

// Course Carousel
let currentCourseSlide = 0;
const courseSlides = document.querySelectorAll('.course-slide').length;
const courseTrack = document.querySelector('.course-track');
const courseDots = document.querySelectorAll('.course-carousel .slider-dot');

function goToCourseSlide(slideIndex) {
    currentCourseSlide = slideIndex;
    updateCourseSlider();
}

function updateCourseSlider() {
    if (!courseTrack) return;
    
    const slideWidth = document.querySelector('.course-slide').offsetWidth;
    courseTrack.style.transform = `translateX(-${currentCourseSlide * slideWidth}px)`;
    
    // Update dots
    if (courseDots.length > 0) {
        courseDots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentCourseSlide);
        });
    }
}

// Countdown Timer for Offers
function startCountdownTimer(offerId, days, hours, minutes, seconds) {
    const daysElement = document.getElementById(`${offerId}-days`);
    const hoursElement = document.getElementById(`${offerId}-hours`);
    const minutesElement = document.getElementById(`${offerId}-minutes`);
    const secondsElement = document.getElementById(`${offerId}-seconds`);
    
    if (!daysElement || !hoursElement || !minutesElement || !secondsElement) return;
    
    let totalSeconds = days * 86400 + hours * 3600 + minutes * 60 + seconds;
    
    const timerInterval = setInterval(() => {
        if (totalSeconds <= 0) {
            clearInterval(timerInterval);
            return;
        }
        
        totalSeconds--;
        
        const daysLeft = Math.floor(totalSeconds / 86400);
        const hoursLeft = Math.floor((totalSeconds % 86400) / 3600);
        const minutesLeft = Math.floor((totalSeconds % 3600) / 60);
        const secondsLeft = totalSeconds % 60;
        
        daysElement.textContent = daysLeft.toString().padStart(2, '0');
        hoursElement.textContent = hoursLeft.toString().padStart(2, '0');
        minutesElement.textContent = minutesLeft.toString().padStart(2, '0');
        secondsElement.textContent = secondsLeft.toString().padStart(2, '0');
    }, 1000);
}

// Auto slide for testimonials
if (testimonialTrack) {
    setInterval(() => {
        currentTestimonialSlide = (currentTestimonialSlide + 1) % testimonialSlides;
        updateTestimonialSlider();
    }, 5000);
}

// Auto slide for courses
if (courseTrack) {
    setInterval(() => {
        currentCourseSlide = (currentCourseSlide + 1) % 3; // Only 3 groups of courses
        updateCourseSlider();
    }, 4000);
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize counters if they exist on the page
    if (document.getElementById('student-counter')) {
        animateCounter('student-counter', 0, 12500, 2000);
        animateCounter('course-counter', 0, 250, 1500);
        animateCounter('event-counter', 0, 48, 1000);
        animateCounter('instructor-counter', 0, 150, 1500);
    }
    
    // Start countdown timers for offers if they exist
    if (document.getElementById('offer1-days')) {
        startCountdownTimer('offer1', 5, 12, 45, 30);
        startCountdownTimer('offer2', 3, 8, 22, 15);
        startCountdownTimer('offer3', 7, 18, 33, 47);
        startCountdownTimer('offer4', 2, 6, 15, 59);
        startCountdownTimer('offer5', 4, 10, 28, 12);
    }
    
    // Add active class to current page in navigation
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const linkHref = link.getAttribute('href');
        if (linkHref === currentPage || (currentPage === '' && linkHref === 'index.html')) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
    
    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
});