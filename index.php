<?php
/**
* Plugin Name: Milk Woo Stats
* Plugin URI: https://milk-studio.dk/
* Description: Woocommerce Order Stats
* Version: 1.0
* Author: Milk Studio
* Author URI: https://milk-studio.dk/
**/

if ( is_admin() ){ // admin actions
  add_action( 'admin_menu', 'a657_add_menu' );
  //add_action( 'admin_init', 'register_mysettings' );
} else {
  // non-admin enqueues, actions, and filters
}

function a657_add_menu(){
	add_submenu_page('tools.php', 'Woo stats', 'Woo stats', 'administrator', plugin_basename('woo-stats'), 'a657_admin_page_html', 99999999);	
}

function a657_getFlagEmoji($countryCode) {
    $countryCode = strtoupper($countryCode);
    $codePoints = array_map(function($char) {
        return 127397 + ord($char);
    }, str_split($countryCode));
    return html_entity_decode('&#' . implode(';&#', $codePoints) . ';', ENT_NOQUOTES, 'UTF-8');
}



function a657_get_orders(){	
	$query = new WC_Order_Query( array(
	    'limit' => -1,
	    //'limit' => 10,
	    'orderby' => 'date',
	    'order' => 'DESC',
	    'type' => 'shop_order', // orders, and not order_refunds plz
	    'status' => ['wc-completed', 'completed'],
	    //'return' => 'ids',
	) );
	$orders = $query->get_orders();
	//foreach($orders as $o) { echo $o->get_status().' - ';  } die();
	//echo '<pre>'; print_r($orders); die();
	//return $orders;
	$orders_cleansed = [];
	foreach($orders as $o) {
		$oo = (object) [];
		$oo->date_created = $o->get_date_created()->__toString();
		$oo->timestamp_created = $o->get_date_created()->getOffsetTimestamp();
		$oo->total = $o->get_total();
		$oo->total_tax = $o->get_total_tax();
		$oo->shipping_total = $o->get_shipping_total();
		$oo->shipping_tax = $o->get_shipping_tax();
		$oo->currency = $o->get_currency();
		$oo->billing_country = $o->get_billing_country();
		$oo->status = $o->get_status();
		$oo->link = get_edit_post_link($o->get_id(),'url'); //'https://thomasdambo.com/wp-admin/post.php?post=6918&action=edit';
		$oo->billing_country_flag = a657_getFlagEmoji($oo->billing_country);
		$orders_cleansed[] = $oo;
	}
	return $orders_cleansed;
}

function a657_admin_page_html(){
	$orders = a657_get_orders();
	?>
		<script src="https://unpkg.com/vue@3/dist/vue.global.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
		<style>
			.x828 {
				width:100%;
				td {
					padding: 0.8em 1em;
					&:first-child { padding-left:0; text-align:left; }
					&:last-child { padding-right:0; text-align:right; }
					border-bottom: 1px solid rgba(0,0,0,0.1);
					&.align-l { text-align: left !important; }
					&.align-l:last-child { text-align: left !important; }
				}
			}
			.x827 {
				width:100%;
				td {
					padding: 0.8em 1em;
					&:first-child { padding-left:0; text-align:left; }
					&:last-child { padding-right:0; text-align:right; }
					border-bottom: 1px solid rgba(0,0,0,0.1);
					&.align-l { text-align: left !important; }
					&.align-l:last-child { text-align: left !important; }
					a {
						text-decoration: none;
					}
				}
			}
		</style>
		<div class="wrap">
			<h1>Woo Stats</h1>
			<p>&mdash; By Milk Studio</p>
			
			<div id="a657">...</div>

			<template id="a657-tpl">
				<div class="card">
					<h2 class="title">Filter</h2>
					<table class="x828">
						<tr>
							<td>Currency</td>
							<td>
								<select v-model="filter_currency">
									<option value="all">Select</option>
									<option v-for="(x) in currencies" :value="x">{{x}}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td>Billing country</td>
							<td>
								<select v-model="filter_country">
									<option value="all">All</option>
									<option v-for="(x) in countries" :value="x">{{x}}</option>									
								</select>
							</td>
						</tr>
						<tr>
							<td>Date from</td>
							<td><input type="date" v-model="date_from"></td>
						</tr>
						<tr>
							<td>Date to</td>
							<td><input type="date" v-model="date_to"></td>
						</tr>
						
					</table>					
				</div>	
				
				<div class="card" v-if="orders_filtered.length">
					<h2 class="title">Orders stats</h2>
					<table class="x828">				
						<tr>
							<td>Orders found</td>
							<td>{{orders_filtered.length}}</td>
						</tr>
						<tr v-if="filter_country=='all'" v-for="(x) in countries">
							<td>— {{x||'Unknown country'}}</td>
							<td>{{num(countriesTotal[x])}} {{filter_currency}}</td>
						</tr>
						<tr>
							<td>Total</td>
							<td>{{num(orders_total)}} {{filter_currency}}</td>
						</tr>
						<?php if(0): ?>
						<tr>
							<td>Total tax</td>
							<td>{{num(orders_total_tax)}} {{filter_currency}}</td>
						</tr>
						<?php endif;?>
						<tr>
							<td>- Shipping total</td>
							<td>{{num(orders_shipping_total)}} {{filter_currency}}</td>
						</tr>
						<?php if(0): ?>
						<tr>
							<td>- Shipping tax</td>
							<td>{{num(orders_shipping_tax)}} {{filter_currency}}</td>
						</tr>
						<?php endif;?>						
					</table>					
				</div>	
				
				<div class="card" v-if="orders_filtered.length">
					<h2 class="title">Orders</h2>
					<table class="x827">
						<tr> <td>Country</td> <td>Date</td> <td>Total</td> <td>Currency</td> <td>Link</td> </tr>
						<tr v-for="(o,oi) in orders_filtered" :key="'x987'+oi">
							<td>{{o.billing_country ? o.billing_country_flag : 'Unknown country'}}</td>
							<td>{{o.date_created.split('T')[0]}}</td>
							<td>{{num(o.total)}}</td>
							<td>{{o.currency||'?'}}</td>
							<td><a :href="o.link">↗️</a></td>
						</tr>
					</table>					
				</div>		
				
				<pre v-if="0">{{orders_filtered}}</pre>	
				
				<br>
				
				<div class="button button-primary" @click="downloadCsv()">Download CSV</div>
				
			</template>
			
			<?php if(0): ?>
			<pre><?php print_r($orders); ?></pre>
			<?php endif; ?>

		</div>
		<script>
			function dedupe(arr) { return Array.from(new Set(arr)) }
			
			window.a657_orders = JSON.parse('<?php echo json_encode($orders,JSON_UNESCAPED_SLASHES);?>')
			const { createApp, ref, reactive, computed } = Vue
			document.querySelector('#a657').innerHTML = 			document.querySelector('#a657-tpl').innerHTML
			let app = createApp({
				setup() {
					
					const filter_country = ref('all')
					const filter_currency = ref('all')
					const orders = reactive(window.a657_orders)
					
					const date_from = ref(undefined)
					const date_to = ref(undefined)
					
					const timestamp_from = computed(()=>{ 
						if(!date_from.value) return
						let d = new Date(date_from.value)
						return d.getTime()
					})
					
					const timestamp_to = computed(()=>{ 
						if(!date_to.value) return
						let d = new Date(date_to.value)
						return d.getTime()
					})


					const countries = computed(()=>{ 
						let c = orders.map(x => x.billing_country)
						return dedupe(c)
					})
					
					const countriesTotal = computed(()=>{ 
						let x = {}
						if(!countries) return
						countries.value.forEach((c)=>{
							let orders = orders_filtered.value.filter((x)=>{ return (x.billing_country === c) })
							if(!orders) return x[c] = 0							
							x[c] = x_total(orders,'total')
						})
						return x
					})
					
					const currencies = computed(()=>{ 
						let c = orders.map(x => x.currency)
						return dedupe(c)
					})
					
					const orders_filtered = computed(()=>{
						let o = orders
						if(filter_country.value !== 'all') o = o.filter((x)=>{ return (x.billing_country === filter_country.value) })
						o = o.filter((x)=>{ return (x.currency === filter_currency.value) })
						if(timestamp_from.value) o = o.filter((x)=>{ return (fixTimestamp(x.timestamp_created) >= fixTimestamp(timestamp_from.value)) })
						if(timestamp_to.value) o = o.filter((x)=>{ return (fixTimestamp(x.timestamp_created) <= fixTimestamp(timestamp_to.value)) })
						return o
					})
					
					let fixTimestamp = (x)=>{ return Number((x+'').slice(0,10)) }
					
					let x_total = (a,y)=>{
						let o = JSON.parse(JSON.stringify(a))
						return o.reduce((v,x)=>{ 							
							let n = v+Number(x[y]) 
							return n
						}, 0)
					}
					
					let downloadCsv = ()=>{

						let rows

						rows = [
						    ["name1", "city1", "some other info"],
						    ["name2", "city2", "more info"]
						]
						rows = orders_filtered.value.map((x)=>{ return Object.values(Object.assign({},x)) })
						rows.unshift(Object.keys(Object.assign({},orders_filtered.value[0])))
						//console.log(rows)																		
						downloadCsvFile(rows, 'orders.csv')						
						
						rows = [
						    ['currency', filter_currency.value||'Undefined'],
						    ['billing_country', filter_country.value],
						    ['date_from', date_from.value||'Undefined'],
						    ['date_to', date_to.value||'Undefined'],						    
						]
						
						//console.log(rows)
						
						countries.value.forEach((x)=>{
							rows.push([
								x||'Unknown country', num(countriesTotal.value[x], 2, '.', '') + ' ' + filter_currency.value
							])
						})
						
						rows.push(['total', num(orders_total.value, 2, '.', '') + ' ' + filter_currency.value])												
						rows.push(['shipping_total', num(orders_shipping_total.value, 2, '.', '') + ' ' + filter_currency.value])
						
						//console.log(rows)
						
						downloadCsvFile(rows, 'order-stats.csv')						
					}
					
					let downloadCsvFile = (rows, filename)=>{
						let csvContent = "data:text/csv;charset=utf-8,"							
						rows.forEach(function(rowArray) {
						    let row = rowArray.join(",")
						    csvContent += row + "\r\n"
						})						
						let encodedUri = encodeURI(csvContent)
						let link = document.createElement('a')
						link.setAttribute("href", encodedUri)
						link.setAttribute("download", filename)
						document.body.appendChild(link) // Required for FF						
						link.click(); // This will download the data file named "my_data.csv".												
					}
					
					let num = function(n, decimals = 2, dec_point = ',', thousands_sep = '.') {
					    dec_point = typeof dec_point !== 'undefined' ? dec_point : '.'
					    thousands_sep = typeof thousands_sep !== 'undefined' ? thousands_sep : ','
					    let parts = Number(n).toFixed(decimals).split('.')
					    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousands_sep)					
					    return parts.join(dec_point)
					}
					
					const orders_total = computed(()=>{ return x_total(orders_filtered.value,'total') })
					const orders_total_tax = computed(()=>{ return x_total(orders_filtered.value,'total_tax') })
					const orders_shipping_total = computed(()=>{ return x_total(orders_filtered.value,'shipping_total') })
					const orders_shipping_tax = computed(()=>{ return x_total(orders_filtered.value,'shipping_tax') })
					
					return { downloadCsv, num, countriesTotal, orders, countries, currencies, orders_filtered, filter_country, filter_currency, orders_total, orders_total_tax, orders_shipping_total, orders_shipping_tax, timestamp_from, timestamp_to, date_from, date_to }
					
				}
			}).mount('#a657')
			window.a657_vue = app
			  
		</script>
	<?php	
}



