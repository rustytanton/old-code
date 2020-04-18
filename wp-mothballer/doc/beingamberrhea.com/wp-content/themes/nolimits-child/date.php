	<?php get_header();?>
	<?php
		global $query_string;
		$query_string .= '&posts_per_page=-1';
		query_posts( $query_string );
	?>
	<div id="content">
		<?php if(have_posts()) : ?>
			<?php $post = $posts[0]; /* Hack. Set $post so that the_date() works. */ ?>
			<?php if (is_day()) { ?>
				<h3><?php the_time('l, F jS, Y'); ?></h3>					
				<?php } elseif (is_month()) { ?>
					<h3><?php the_time('F Y'); ?></h3>
				<?php } elseif (is_year()) { ?>
					<h3><?php the_time('Y'); ?></h3>		
				<?php } ?>				
				
				<ul>			
				<?php while(have_posts()) : the_post(); ?>	
					<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>	
				<?php endwhile; ?>
				</ul>
			
			<?php else : ?>
				<div class="post">
				<h2><?php _e('Not Found'); ?></h2>
				</div>
			<?php endif; ?>			
	</div>
<?php get_sidebar();?>	
<?php get_footer();?>