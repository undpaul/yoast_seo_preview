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

        var snippetPreview = new DrupalSeoPreview.SnippetPreview({
          targetElement: snippetTarget,
          baseURL: args['baseURL'],
          data: {
            title: args['title'],
            urlPath: args['urlPath'],
            metaDesc: args['text']
          }
        });

        // Disable snippet editor events.
        snippetPreview.bindEvents = function () {};

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
            }
          }
        });

        app.refresh();
      });
    }
  };

}(jQuery, Drupal));
