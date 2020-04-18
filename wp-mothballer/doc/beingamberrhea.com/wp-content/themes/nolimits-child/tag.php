<?php get_header(); ?>
	<?php
		global $query_string;
		$query_string .= '&posts_per_page=-1';
		query_posts( $query_string );
	?>
	<div id="content" class="narrowcolumn">
		<h3>Tag Archive: <?php echo single_cat_title(); ?></h3>
		<?php if(have_posts()) : ?>
			<ul>
			<?php while(have_posts()) : the_post(); ?>
				<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
			<?php endwhile; ?>
			</ul>
		
		<?php else : ?>
			<h2 class="center">Not Found</h2>
			<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
		<?php endif; ?>
	</div>
<?php get_sidebar();?>
<?php get_footer(); ?>