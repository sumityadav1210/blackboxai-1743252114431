    <footer class="mt-auto py-3 bg-light border-top">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <span class="text-muted">&copy; <?= date('Y') ?> Student Management System</span>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="text-decoration-none me-3" data-bs-toggle="tooltip" title="GitHub Repository">
                        <i class="bi bi-github"></i>
                    </a>
                    <a href="#" class="text-decoration-none me-3" data-bs-toggle="tooltip" title="Follow us on Twitter">
                        <i class="bi bi-twitter"></i>
                    </a>
                    <a href="#" class="text-decoration-none" data-bs-toggle="tooltip" title="Contact Support">
                        <i class="bi bi-envelope"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/app.js"></script>
    <script>
        // Initialize all tooltips and popovers
        $(function () {
            // Tooltips
            $('[data-bs-toggle="tooltip"]').tooltip()
            
            // Popovers
            $('[data-bs-toggle="popover"]').popover()
            
            // Smooth scrolling
            $('a[href*="#"]').on('click', function(e) {
                e.preventDefault()
                $('html, body').animate(
                    { scrollTop: $($(this).attr('href')).offset().top },
                    500,
                    'linear'
                )
            })
        });
    </script>
</body>
</html>
