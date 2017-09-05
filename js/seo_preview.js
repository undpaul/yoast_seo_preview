/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.seoPreview = {
    attach: function (context, settings) {
      $('#edit-yoast-seo-preview', context).once('seo-preview').each(function() {
        var seoPreview = new YoastSEO.seoPreview();
        var args = {
          targets: {
            output: "output",
            snippet: "snippet"
          },
          callbacks: {
            getData: seoPreview.getData.bind( seoPreview ),
            bindElementEvents: seoPreview.bindElementEvents.bind( seoPreview ),
            saveScores: seoPreview.saveScores.bind( seoPreview )
          },
          locale: "de_DE"
        };
        YoastSEO.app = new YoastSEO.App( args );
        YoastSEO.app.refresh();
      });
    }
  };

  YoastSEO.seoPreview = function( args ) {
    this.config = args;
  };

  /**
   * Get data from inputfields and store them in an analyzerData object. This object will be used to fill
   * the analyzer.
   *
   * @returns {{keyword: string, pageTitle: string, text: string}}
   */
  YoastSEO.seoPreview.prototype.getData = function() {
    return {
      keyword: document.getElementById( "edit-keyword" ).value,
      pageTitle: drupalSettings.seo_preview.pageTitle,
      text: drupalSettings.seo_preview.body
    };

  };

  /**
   * Calls the eventbinders.
   */
  YoastSEO.seoPreview.prototype.bindElementEvents = function( app ) {
    $("#edit-yoast-seo-preview-button").on("change", app.analyzeTimer.bind( app ) );
  };

  /**
   * Called by the app to save scores. Currently only returns score since
   * there is no further score implementation
   * @param score
   */
  YoastSEO.seoPreview.prototype.saveScores = function( score ) {
    var rating = 0;
    if (typeof score == "number" && score > 0) {
      rating = ( score / 10 );
    }
    document.getElementById( "scores" ).innerHTML = rating;
  };

}(jQuery, Drupal, drupalSettings));
