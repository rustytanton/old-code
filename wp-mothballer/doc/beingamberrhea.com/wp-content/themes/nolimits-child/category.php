<?php get_header();?>	
	<div id="content">
		<?php
			global $query_string;
			$query_string .= '&posts_per_page=-1';
			query_posts( $query_string );
		?>
		<?php if(have_posts()) : ?>
			<h3>Category Archive: <?php echo single_cat_title(); ?></h3>			
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
<?php get_sidebar()?>	
<?php get_footer()?>