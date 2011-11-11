<?php get_header(); ?>
<div id="content">
    <?php include_once(ABSPATH . 'wp-content/themes/lastminutetheatretickets/sidebar-left.php'); ?>
    <div class="main">
        <?php if(have_posts()):  ?>          
           
                <?php while (have_posts()) : the_post(); ?>
                    <div <?php post_class() ?> id="post-<?php the_ID(); ?>">
                        <h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
                        <p class="postmetadata">Posted on <?php the_time('F jS, Y'); ?> by <?php the_author(); ?> in <?php the_category(', ') ?> &raquo; <?php comments_popup_link('No Comments', '1 Comment ', '% Comments'); ?></p>
                        <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>"><?php the_post_thumbnail(); ?></a>

                        <div class="entry">
                            <?php global $post; if($post->ID == get_option('theatre_post_all_casts'))echo $post->post_content ;else the_excerpt(); ?>
                            <div class="clear"></div>
                        </div>
                        <!-- <p class="postmetadata"><?php the_tags('Tags: ', ', ', ''); ?> </p>-->
                    </div>
                <?php endwhile; ?>
                <div class="navigation">
                    <div class="alignleft"><?php next_posts_link('&laquo; Older Entries') ?></div>
                    <div class="alignright"><?php previous_posts_link('Newer Entries &raquo;') ?></div>
                </div>
            <?php
            else :
                if (is_category()) {
                    printf("<h2 class='center'>Sorry, but there aren't any posts in the %s category yet.</h2>", single_cat_title('', false));
                } else if (is_date()) { // If this is a date archive
                    echo("<h2>Sorry, but there aren't any posts with this date.</h2>");
                } else if (is_author()) { // If this is a category archive
                    $userdata = get_userdatabylogin(get_query_var('author_name'));
                    printf("<h2 class='center'>Sorry, but there aren't any posts by %s yet.</h2>", $userdata->display_name);
                } else {
                    echo("<h2 class='center'>No posts found.</h2>");
                }
                ?>
            <?php endif; ?>

        </div>
        <?php get_sidebar(); ?>
    </div>
    <?php get_footer(); ?>
