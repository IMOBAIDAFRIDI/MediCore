// Auto-process email queue every 5 seconds
setInterval(function () {
    fetch(URLROOT + '/process_email_queue.php')
        .then(response => response.json())
        .then(data => {
            if (data.sent > 0) {
                console.log('📧 Emails processed: ' + data.sent);
            }
        })
        .catch(err => console.error('Email queue error:', err));
}, 5000); // Run every 5 seconds
