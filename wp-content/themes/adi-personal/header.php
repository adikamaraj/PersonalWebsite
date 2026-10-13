<?php $projects_selected=is_post_type_archive('adi_project') || is_tax('adi_space') || is_singular('adi_project'); ?>
<!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?><a class="skip-link" href="#main">Skip to content</a>
<header class="site-header wrap" id="top">
<a class="wordmark" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Adithya Kamaraj, home">adithya<span class="wordmark-dot">.</span><span class="wordmark-sub">KAMARAJ</span></a>
<nav class="view-switch" aria-label="Site view"><a href="<?php echo esc_url(home_url('/')); ?>" class="<?php echo is_front_page()?'is-active':''; ?>" <?php echo is_front_page()?'aria-current="page"':''; ?>>Home</a><a href="<?php echo esc_url(adi_projects_url()); ?>" class="<?php echo $projects_selected?'is-active':''; ?>" <?php echo $projects_selected?'aria-current="page"':''; ?>>Projects</a></nav>
<button class="menu-toggle" type="button" aria-label="Menu" aria-expanded="false" aria-controls="site-navigation"><span>Menu</span><span class="menu-lines" aria-hidden="true"></span></button>
<nav class="site-nav" id="site-navigation" aria-label="Main navigation"><a href="<?php echo esc_url(home_url('/#contributions')); ?>">Research</a><a href="<?php echo esc_url(adi_page_url('writing')); ?>" <?php echo is_home()?'aria-current="page"':''; ?>>Writing</a><a class="nav-contact" href="#contact">Let’s talk <?php echo adi_arrow(); ?></a></nav>
</header>
