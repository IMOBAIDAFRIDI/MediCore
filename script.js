document.addEventListener('DOMContentLoaded', function() {
    const carousels = document.querySelectorAll('.hero-carousel');
    
    carousels.forEach(carousel => {
        const slides = carousel.querySelectorAll('.carousel-slide');
        if (slides.length === 0) return;
        
        let currentSlide = 0;
        
        // Show first slide initially
        slides[0].classList.add('active');
        
        setInterval(() => {
            // Fade out current
            slides[currentSlide].classList.remove('active');
            
            // Calculate next
            currentSlide = (currentSlide + 1) % slides.length;
            
            // Fade in next
            slides[currentSlide].classList.add('active');
        }, 5000); // Change every 5 seconds
    });
});
