<style type="text/css">
.options { text-align:right; padding-right:20px; }
#placeholder { width:100%; height:300px; }
#time_ini,#time_end { width:100px; }
.user-posts { display:none; }
.data-point-label { font-size:9px; color:#444444; }
</style>
<div class="wrap ps-stats">
<div class="icon32" id="icon-options-general"><br></div><h2><?php _e( 'Publishing Statistics', 'ps' ); ?></h2>

    <div class="options">
    <form method="GET" action="">
        <label for="time_ini"><?php _e( 'From', 'ps' ); ?>: <input type="text" name="time_ini" id="time_ini" value="<?php echo $time_ini; ?>" /></label>
        <label for="time_end"><?php _e( 'To', 'ps' ); ?>: <input type="text" name="time_end" id="time_end" value="<?php echo $time_end; ?>" /></label> 
        <label for="role"><?php _e( 'Role' ); ?>: 
            <select name="role" id="role">
                <option value=""><?php _e( 'All' ); ?></option>
                <option value="admin" <?php echo ($role == "admin" ? "selected" : ""); ?>><?php _ex( 'Admin', 'User role' ); ?></option>
                <option value="editor" <?php echo ($role == "editor" ? "selected" : ""); ?>><?php _ex( 'Editor', 'User role' ); ?></option>
                <option value="author" <?php echo ($role == "author" ? "selected" : ""); ?>><?php _ex( 'Author', 'User role' ); ?></option>
                <option value="contributor" <?php echo ($role == "contributor" ? "selected" : ""); ?>><?php _ex( 'Contributor', 'User role' ); ?></option>
            </select>
        </label> 
        <input type="hidden" name="orderby" value="<?php echo $orderby; ?>" />
        <input type="hidden" name="sort" value="<?php echo $sort; ?>" />
        <input type="submit" class="button" value="<?php _e( 'Filter', 'ps' ); ?>" />
        <input type="hidden" name="page" value="ps" />
    </form>
    </div>
    
    <div id="placeholder"></div>
    
    <h4><?php _e( 'Totals', 'ps' ); ?></h4>

    <table class="widefat">

        <thead>
            <th><?php _e( 'Users', 'ps' ); ?></th>
            <th><?php _e( 'Posts', 'ps' ); ?></th>
            <th><?php _e( 'Days', 'ps' ); ?></th>            
            <th><?php _e( 'Posts/day', 'ps' ); ?></th>
        </thead>

        <tr>
            <td><?php echo $totals['users']; ?></td>
            <td><?php echo $totals['posts']; ?></td>
            <td><?php echo $totals['days']; ?></td>
            <td><?php echo $totals['posts_per_day']; ?></td>
        </tr>

    </table>

    <h4><?php _e( 'Users', 'ps' ); ?></h4>

    <table class="widefat">
        <thead>
            <th class="<?php echo $class_column_display_name; echo $display_name_sort; ?>"><a href="<?php echo add_query_arg( array( "orderby" => "display_name", "sort" => $display_name_sort )); ?>"><span><?php _e( 'User', 'ps' ); ?></span><span class="sorting-indicator"></span></a></th>
            <th class="<?php echo $class_column_post_count; echo $post_count_sort; ?>"><a href="<?php echo add_query_arg( array ("orderby" => "post_count", "sort" => $post_count_sort )); ?>"><span><?php _e( 'Posts', 'ps' ); ?></span><span class="sorting-indicator"></span></th>
            <th class="<?php echo $class_column_posts_per_day; echo $posts_per_day_sort; ?>"><a href="<?php echo add_query_arg( array( "orderby" => "posts_per_day", "sort" => $posts_per_day_sort )); ?>"><span><?php _e( 'Posts/day', 'ps' ); ?></span><span class="sorting-indicator"></span></th>
        </thead>

    <?php foreach( $userdata as $k => $v ) : ?>
    
        <tr>
            <td>
                <a href="user-edit.php?user_id=<?php echo $k; ?>"><?php echo $v['display_name']; ?></a>
            </td>
            <td>
                <a href="javascript:void(0);" class="show-user-posts"><?php echo $v['post_count']; ?> post<?php echo $v['post_count'] > 1 ? 's' : ''; ?></a>
                <div class="user-posts" data-posts="<?php echo implode( ',', $v['posts'] ); ?>"></div>
            </td>
            <td><?php echo $v['posts_per_day']; ?></td>
        </tr>

    <?php endforeach; ?>
    
    </table>
</div>
