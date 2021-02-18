<?php
get_header();

$args_t = [
  'taxonomy'  => 'events_tags',
  'hide_empty'    => true, 
  'pad_counts'  => true
];
$terms = get_terms($args_t);
$terms = wp_list_filter( $terms, array('parent' => 0) );

// пройдемся по терминам верхнего уровня
if ($terms) {
  foreach ($terms as $key => $term) {
    // получим посты термина    
    $request = get_events_by_tags($term);

    if ($request->have_posts()) {
      echo '<hr>';
      
      echo get_start_tab_term($term, $request->post_count);

      $calendar = render_calendar($request);
    
      // пройдемся по всему календарю и выведем даты и их карточки event
      foreach ($calendar as $mounth) {
        echo '<br><span style="color:red;">calendar date: </span> ';
        echo date_i18n('F.Y', $mounth['date']) . '<br>';
        foreach ($mounth['event_cards'] as $event) {
          echo $event;
        }
      }
    
      echo get_end_tab_term($term);
    }
  }
}

get_footer();

// functions tab calendar events

function get_events_by_tags(WP_Term $cat = null) {
  $d = strtotime("+1 day");
  $now = date('Y-m-d 00:00:00', $d);

  $args_q = [
    'post_type'       => 'events',
    'post_status'     => 'publish',
    'posts_per_page'  => -1,
    'meta_query'      => array(
      'relation'        => 'AND',
      'events_finish' => array(
          'key'       => 'events_finish',
          'type'      => 'DATETIME',
          'value'     => $now,
          'compare'   => '>'
      ),
      'events_start' => array(
          'key'     => 'events_start',
          'type'    => 'DATETIME',
      )
    ),
    'orderby'   => array(
      'events_start'   => 'ASC'
    ),
    'tax_query' => array(
      'relation'  => 'AND',
      'events_tags' => [
        'taxonomy'  => 'events_tags',
        'field'    => 'slug',
        'terms'     => $cat,
        'operator'  => 'IN'
      ]
    )
  ];
  
  return new WP_Query($args_q);
}

function render_calendar(WP_Query $request) {
  $calendar = [];

  // выберем все уникальные месяцы и год старта event

  while ($request->have_posts()) {
    $request->the_post();

    $e_start = strtotime(get_field('events_start'));
    $e_start = strtotime(date('00.m.Y', $e_start));
    $calendar_item = wp_list_filter( $calendar, array('date' => $e_start) );
    
    // сложим уникальные даты в массив
    if (!count($calendar_item)) {
      $calendar[]['date'] = $e_start;
    }
    // срендерим карточку event
    $calendar[count($calendar) - 1]['event_cards'][] = get_cards_html();
  }
  wp_reset_postdata();

  return $calendar;
}

function get_cards_html() {
  global $post;
  $card_html = "<p><br>the_title: ";
  $card_html .= get_the_title();
  $card_html .= "<br> post->ID: ";
  $card_html .= $post->ID;
  $card_html .= "<br> the_content: ";
  $card_html .= get_the_content();
  $card_html .= "<br> events_start: ";
  $card_html .= get_field('events_start');
  $card_html .= "<br> events_finish: ";
  $card_html .= get_field('events_finish');
  $card_html .= "</p>";
  return $card_html;
}

function get_start_tab_term($term, $count) {
  $result = 'CatName: ' . $term->name . '; count: ' . $count . ';<br>';
  return $result;
}
function get_end_tab_term($term) {
  // return 'end terms tab <hr>';
}