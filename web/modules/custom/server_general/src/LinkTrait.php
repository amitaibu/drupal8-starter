<?php

namespace Drupal\server_general;

use Drupal\Core\Url;

/**
 * Helper method for building a link, and decorating it.
 */
trait LinkTrait {

  /**
   * Build a link.
   *
   * @param string|\Drupal\Core\Url $url
   *   The URL string or object.
   * @param string $title
   *   The title of the link.
   * @param string $color
   *   The color of the link. Hover color will be calculated from it.
   *   see `server-theme-text-decoration--link.html.twig`.
   * @param string $underline
   *   Determine if an underline should appear. Possible values are:
   *   - `always`: Always show.
   *   - `hover`: Show only on hover.
   *   - NULL: No underline at all.
   * @param bool $show_external_icon
   *   Determine if an external icon suffix should appear if the URL is
   *   external. Defaults to TRUE.
   *
   * @return array
   *   Render array.
   */
  public function buildLink(string|Url $url, string $title, string $color, string $underline = 'always', bool $show_external_icon = TRUE): array {
    if (is_string($url)) {
      $url = Url::fromUri($url);
    }
    $element = [
      '#theme' => 'server_theme_link',
      '#url' => $url,
      '#title' => $title,
      '#show_external_icon' => $show_external_icon,
    ];

    return [
      '#theme' => 'server_theme_text_decoration__link',
      '#color' => $color,
      '#underline' => $underline,
      '#element' => $element,
    ];
  }

}
