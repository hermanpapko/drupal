(function ($, Drupal, once) {
  Drupal.behaviors.myHelperLiveTime = {
    attach: function (context) {
      once('live-time-init', '.live-time', context).forEach(function (element) {
        setInterval(function() {
          const now = new Date();
          // 3. Format and write (fixed el to element)
          element.textContent = now.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
          });
        }, 1000);
      });
    }
  };
})(jQuery, Drupal, once);
