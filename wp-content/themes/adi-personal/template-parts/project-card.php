<?php
$terms = get_the_terms( get_the_ID(), 'adi_space' );
$space = $terms && ! is_wp_error( $terms ) ? implode( ' ', wp_list_pluck( $terms, 'slug' ) ) : 'design-ai';
$label = $terms && ! is_wp_error( $terms ) ? $terms[0]->name : 'Project';
$visual = get_post_meta( get_the_ID(), 'adi_visual', true ) ?: 'canvas';
?>
<article class="project-card" data-space="<?php echo esc_attr( $space ); ?>"><a href="<?php the_permalink(); ?>"><div class="project-image"><?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); else : adi_visual( $visual ); endif; ?><span class="project-open"><?php echo adi_arrow(); ?></span></div><div class="project-meta"><span><?php echo esc_html( $label ); ?></span><span><?php echo esc_html( get_post_meta( get_the_ID(), 'adi_year', true ) ); ?></span></div><h3><?php the_title(); ?></h3><p><?php echo esc_html( get_the_excerpt() ); ?></p></a></article>
