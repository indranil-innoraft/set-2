(function($, Drupal, drupalSettings) {
  Drupal.behaviors.mobileValidation = {
    attach: function (context, settings) {
      const body = drupalSettings.read_more.body;
      const rawData = body;
      const firstFiftyWords = body.split(/\s+/).slice(0, 50).join(" ");

      $('.read-less', context).click(function () {
        $('.body-wrapper').html(firstFiftyWords);
      });

      $('.read-more', context).click(function () {
        $('.body-wrapper').html(rawData);
      });
    }
  }
}) (jQuery, Drupal, drupalSettings);
