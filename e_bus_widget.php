<?php
class  e_bus_widget extends WP_Widget {

	/* All code for widget goes here */

	/**
	 * Constructor
	 * 
	 * Registers the widget with WordPress. Sets up the widget
	 * ID, name, and description.
	 */
	public function __construct() 
	{
		parent::__construct(
			'e_bus__widget', // Base ID
			__( 'E Bus Widget', 'e_bus_widget' ), // Name
			array( 'description' => __( 'Displays some db info', 'e_bus_widget' ), ) // Args
		);
	}
	
	/**
	 * Admin Form
	 * 
	 * Displays the form in the admin area. This contains all the
	 * settings for the widget, including widget title and anything
	 * else you may have.
	 */
	public function form( $instance ) 
	{
		$title         = ! empty( $instance['title'] ) ? $instance['title'] : '';
		//$size          = ! empty( $instance['size'] ) ? $instance['size'] : 'full';
		//$allowed_sizes = $this->get_sizes();
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'e_bus_widget' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}
	
	/**
	 * Update Values
	 *
	 * Sanitize widget form values as they are saved. Make sure
	 * everything is safe to be added in the database.
	 */
	public function update( $new_instance, $old_instance ) 
	{
		$instance          = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;	
		
	}

	/**
	 * Front-End Display
	 * 
	 * Display the contents of the widget on the front-end of the
	 * site.
	 */
	public function widget( $args, $instance ) 
	{
	
		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		
		echo $args['after_widget'];
		$viewed_array = $this->compare_id_to_db();
		$top_ratings_array = $this->get_similar_rated_products();
		$this->display_recommendations($viewed_array, $top_ratings_array);
		
	}
	function display_recommendations(array $viewed_array, array $top_ratings_array)
	{
		$max_rating_array = $top_ratings_array[0];
		//echo "<br> max rating array <br>";
		//var_dump($max_rating_array);
		$rec_prod_names = $top_ratings_array[1];
		
		$recommend_products = $viewed_array[0];
		//echo "<br> recommend_products <br>";
		//var_dump($recommend_products);
		$rec_prod_names2 = $viewed_array[1];
		
		//Remove duplicates
		foreach($max_rating_array as $max_key => $max_val)
		{
			//foreach($recommend_products as $rec_key => $rec_val)
			for( $i = 0; $i < count($recommend_products); $i++)
			{
				$id = $recommend_products[$i];
				if($max_key == $id)
				{
					//if a key exists in both arrays delete it 
					//from max_rating_array
					unset( $max_rating_array[$max_key] );
				}
			}
		}
		
		//Display Url of each recommended product
		if ( ! empty( $recommend_products ) ) 
		{
			for( $i = 0; $i < count($recommend_products); $i++)	
			{
				$id = $recommend_products[ $i ];
				$url = get_permalink( $id );
				$name = $rec_prod_names2[ $i ];
				$product = wc_get_product( $id );
				$image = $product->get_image(array( 80, 80));
				?>
				<p>
					<img width="70" height="70" src="'. <?php echo $image ?> 
					<a href= "<?php echo $url?>" ><?php echo $name?></a>
				</p>
				<?php
			}
		}else{
			?>
			<p>No recently viewed items found</p>
			<?php
		}
		
		//Display Recommendations
		?>
		<p>Similar top rated items</p>
		<?php
		if( ! empty($max_rating_array) )
		{
			foreach($max_rating_array as $m_key => $m_val)
			{
				$url = get_permalink( $m_key );
				$name = $rec_prod_names[ $m_key ];
				$product = wc_get_product( $m_key );
				$image = $product->get_image(array( 80, 80));
				?>
				<p>
					<img width="70" height="70" src="'. <?php echo $image ?> 
					<a href= "<?php echo $url?>" ><?php echo $name?></a>
				</p>
				<?php
			}
		}else{
			?>
			<p>No Items with better reviews found</p>
			<?php
		}
	}
	function get_similar_rated_products()
	{
		$y = 0;
		
		//Get cart products ids
		global $woocommerce;
		//Get cart items
		$items = $woocommerce->cart->get_cart_contents();
		//cart_products has the ids of the products in cart in an array
		$cart_products = [];
        foreach($items as $item => $values)
		{ 
            array_push( $cart_products,  $values['data']->get_id());
		}	
		//echo "<br><br> cart_products: <br>";
		//var_dump($cart_products);
		
		$u_id = get_current_user_id();
		if($u_id != 0)
		{		
			//cart_prod_cat has: [id] = parent_category_of_cart_product
			$cart_prod_cat = [];
			foreach($cart_products as $cart_p)
			{
				$p =  wc_get_product( $cart_p );
				$args = array(
					'orderby' => 'term_id'
				);
				if ($p->get_type() == 'variation') {
					$cat = wp_get_object_terms( $p->get_parent_id(), 'product_cat', $args);
					//cat[0] is object of parent category
					//cat[1] is object of child category
					$cart_prod_cat[ $p->get_parent_id() ] = $cat[0]->term_id;
				}else{
					$cat = wp_get_object_terms( $p->get_id(), 'product_cat', $args);
					//cat[0] is object of parent category
					//cat[1] is object of child category
					$cart_prod_cat[ $p->get_id() ] = $cat[0]->term_id;
				}
				//echo "<br> <br> cat: ";
				//var_dump($cat);
			}
			//echo " <br> cart_prod_cat : <br>";
			//var_dump($cart_prod_cat);
			
			//Get all products and save the parents' rating
			$args = array(
				'return' => 'ids',
				'posts_per_page' => -1,
				);
			$products = wc_get_products( $args );
			//var_dump($products);

			//For all products in the site
			$id_rating_array = [];	// [id] = rating
			$id_cat_array = [];		// [id] = cat
			$args = array(
				'orderby' => 'term_id'
			);
			foreach( $products as $product)
			{
				$product_s = wc_get_product( $product );
				if ($product_s->get_type() == 'variation') {
					//echo "parent id : " . $product_s->get_parent_id() . "<br>";
					$rating = get_post_meta( $product_s->get_parent_id(), '_wc_average_rating', true);
					$id_rating_array[ $product_s->get_parent_id() ] = $rating;
					$cat = wp_get_object_terms( $product_s->get_parent_id(), 'product_cat', $args);	
					$id_cat_array[ $product_s->get_parent_id() ] = $cat[0]->term_id;				
				}else{
					//echo "id : " . $product_s->get_id() . "<br>";
					$rating = get_post_meta( $product_s->get_id(), '_wc_average_rating', true);
					$id_rating_array[ $product_s->get_id() ] = $rating;
					$cat = wp_get_object_terms( $product_s->get_id(), 'product_cat', $args);
					//cat[0] is object of parent category
					//cat[1] is object of child category
					//echo "<br> product_s->get_id() : " . $product_s->get_id();
					//echo "<br> cat[0]->term_id : " . $cat[0]->term_id;
					$id_cat_array[ $product_s->get_id() ] = $cat[0]->term_id;	
				}
				//echo "<br> rating <br>";
				//var_dump($rating);

			}
			//echo "<br> id_rating_array <br>";
			//var_dump($id_rating_array);
			//echo "<br> id_cat_array <br>";
			//var_dump($id_cat_array);
			
			//Get products that have been bought by current user
			global $wpdb;
			$tablename = $wpdb->prefix . "wc_order_product_lookup";
			$q = "select product_id from " . $tablename . " where customer_id = %d";
			$bought_products = $wpdb->get_results( $wpdb->prepare( $q , $u_id) );
			//echo " <br> bought_products <br>";
			//var_dump($bought_products);
			//echo "<br> <br>";
			//Get the product (that is in the same
			//category as each of the products in
			//cart) that has the best rating and 
			//save it in max_rating_array
			$max_rating_array = [];
			$max_id = null;
			$max_rating = null;
			$rec_prod_names = [];
			//For all products in the cart
			foreach($cart_prod_cat as $p_key => $p_val)
			{
				$max_id = null;
				$max_rating = null;
				//For all products check if they are in the same category
				//as the cart product
				foreach($id_cat_array as $i_key => $i_val)
				{
					//echo "<br> p_val " . $p_val;
					//echo "<br> i_val " . $i_val . "<br>";
					if($p_val == $i_val)
					{
						//found a product that is in the same category
						//*
						$flag = true;
						//check if it has been bought before by the user
						foreach ($bought_products as $bought_key => $bought_val) 
						{	
							$id = array_values(get_object_vars($bought_val));
							if($i_key == $id[0])
							{
								//echo " found bought item " . $i_key;
								//product has been bought before so break
								$flag = false;
								break;
							}
						}//*/
						//Check if it is already in the cart
						foreach($cart_products as $cart_p)
						{
							$p =  wc_get_product( $cart_p );
							if ($p->get_type() == 'variation') 
							{
								if($p->get_parent_id() == $i_key )
								{
									$flag = false;
									break;
								}
							}else{
								if($p->get_id() == $i_key )
								{
									$flag = false;
									break;
								}
							}
						}
						if( $flag == true )
						{
							//found a product of the same category as cart product
							//store id of product with best rating
							if($id_rating_array[$i_key] >= $max_rating)
							{
								//echo " <br> found better rating " . $i_key;
								$max_rating = $id_rating_array[$i_key];
								$max_id = $i_key;
								$rec_prod_names [ $i_key ] = get_the_title($i_key);
							}
						}
					}
				}
				$max_rating_array[ $max_id ] = $max_rating;
				//display product with max rating
				//echo "<br> max rating : " . $max_rating . "<br> id : " . $max_id . "<br><br>";
			}
			return array($max_rating_array, $rec_prod_names);
		}else{
			echo "User not logged in";
		}
	}
	function compare_id_to_db()
	{
		global $woocommerce;
		global $wpdb;
		$wpdb->show_errors( true );
		
		//Get cart items			
		$items = $woocommerce->cart->get_cart_contents();
		$cart_products = [];
		foreach($items as $item => $values)
		{ 
			array_push( $cart_products,  $values['data']->get_id());
		}	
		//echo "<br><br> cart_products: <br>";
		//var_dump($cart_products);
		
		//get_current_user_id - returns 0 when no user is logged in
		$u_id = get_current_user_id();
		if($u_id != 0)
		{
			//echo "User " . $u_id;
			//get current product id from current page
			$product = wc_get_product();
			if ( ( $product != null ) & ($product != false) & (is_product()) )
			{
				$p_id = $product->get_id();
				//echo "Product ID " . $p_id;
				
				//run query that checks if this product has been viewed previously
				//by the user, if it hasnt been viewed (doesnt exist in the table)
				//add it to the table, if it has been previously viewed 
				//(exists in the table) do nothing
				$tablename = $wpdb->prefix . "user_viewed_products";
				$q = "select product_id from " . $tablename . " where product_id = %d and user_id = %d";
				$results = $wpdb->get_results( $wpdb->prepare( $q , $p_id, $u_id) );
				//var_dump( $results );
				
				if( ! empty( $results ) )
				{
					//the current product exists in the table for the current user
					//so do nothing
					//echo "product exists in table";
				}else{
					//the current product doesnt exist for the current user in the
					//table so add it
					$q = "insert into " . $tablename . " values(%d,%d)";
					$wpdb->query( $wpdb->prepare($q, $u_id, $p_id) );
					//echo "product added to table";
				}
			}
						
			//Refresh results in case we just added an entry in the table
			$tablename = $wpdb->prefix . "user_viewed_products";
			$tablename1 = $wpdb->prefix . "woocommerce_order_items";
			$tablename2 = $wpdb->prefix . "wc_order_product_lookup";
			$q = "select product_id from " . $tablename . " where user_id = %d";
			$viewed_products = $wpdb->get_results( $wpdb->prepare( $q , $u_id) );
			
			//Get products that have been bought by current user
			$tablename = $wpdb->prefix . "wc_order_product_lookup";
			$q = "select product_id from " . $tablename . " where customer_id = %d";
			$bought_products = $wpdb->get_results( $wpdb->prepare( $q , $u_id) );
			
			$recommend_products = [];
			$rec_prod_names = [];
			//Compare product lists - if a product exists in bought_products
			//dont add it to the recommend_products
			foreach ($viewed_products as $viewed) 
			{
				//if flag remains false at the end of the next loop
				//the product hasnt been bought by the user
				//so add it to the recommend_products array
				$flag = false;	
				foreach ($bought_products as $bought) 
				{
					if($viewed == $bought)
					{
						$flag = true;
						break;
					}
				}
				//Check if item has been added to the cart
				//if it has been added to the cart, dont add it 
				//to the list of recommendations
				foreach($cart_products as $cart_p)
				{
					//echo "cart_p : " . $cart_p;
					//echo "v->p_id : " . $viewed->product_id;
					$p =  wc_get_product( $cart_p );
					$cat =  wc_get_product_category_list($p->get_parent_id());
					//echo "<br> <br> ar: ";
					//var_dump($cat);
					//*/
					//c[0] has parent category, c[1] has child category
					$c = explode( ',', $cat);
					//echo "c : ";
					//var_dump($c);
					//echo "<br> c[0] : " . $c[0] . "<br>";
					if ($p->get_type() == 'variation') {
						if($viewed->product_id == $p->get_parent_id())
						{
							$flag = true;
							break;
						}
					}else{
						if($viewed->product_id == $p->get_id())
						{
							$flag = true;
							break;
						}
					}
				}
				if($flag == false)
				{
					array_push($recommend_products, $viewed->product_id);
					array_push($rec_prod_names, get_the_title($viewed->product_id) );
				}
			}
			return array($recommend_products, $rec_prod_names);

		}else{
			echo "User not logged in";
		}
	}
}