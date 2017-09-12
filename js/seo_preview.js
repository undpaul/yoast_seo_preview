/**
 * @file
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.seoPreview = {
    attach: function (context) {

      $(context).on('seoPreviewOpen', function (event, args) {
        /* global YoastSeoDrupal */
        var DrupalSeoPreview = new YoastSeoDrupal();
        var snippetTarget = $('#yoast-seo-preview-snippet', context).get(0);
        var throbber = $('<div class="ajax-progress ajax-progress-throbber"></div>');
        throbber.append('<div class="throbber">&nbsp;</div>');

        var snippetPreview = new DrupalSeoPreview.SnippetPreview({
          targetElement: snippetTarget,
          baseURL: args['baseURL'],
          data: {
            title: args['title'],
            urlPath: args['urlPath'],
            metaDesc: args['metaDesc']
          }
        });

        // Disable snippet editor events.
        snippetPreview.bindEvents = function () {};

        $(snippetTarget).after(throbber);

        var app = new DrupalSeoPreview.App({
          snippetPreview: snippetPreview,
          targets: {
            output: 'yoast-seo-preview-output'
          },
          callbacks: {
            getData: function () {
              return {
                keyword: args['keyword'],
                text: args['text']
              };
            },
            updatedKeywordsResults: function () {
              throbber.remove();
            }
          }
        });

        app.refresh();

      });
    }
  };

}(jQuery, Drupal));
