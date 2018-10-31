<?php
	// get API keys
	include("../secrets/api-keys.php");
	// $api_key["google_directions"] = "XXXXXXX";
	// $api_key["gas_feed"] = "XXXXXXX";

	// save home address
	if($_GET['home']) {
		if($_GET['home'] != $_COOKIE["home_address"]) {
			setcookie("home_address", $_GET["home"], time()+864000);
		} else {
			setcookie("home_address", "", time()-3600);
		}
	}
	
	if($_GET["home"]) { $home_address = $_GET["home"]; } else { $home_address = $_COOKIE["home_address"]; }
	
	preg_match("/\d{5}(-\d{4})?\b/", $home_address, $home_zipcode);

	function geocode_address($address) {	
		global $api_key;
		
		$url["live_api"] = "https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($address)."&key=".$api_key["google_directions"];
		
        $request = curl_init(); 
        curl_setopt($request, CURLOPT_URL, $url["live_api"]); 
		curl_setopt($request,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
		
        $response = curl_exec($request); 
        curl_close($request);
		
		return json_decode($response, true);
	}
	
	function get_gas_prices_near_location($lat, $lng) {		
		global $api_key;
		
		$gas_price_search["distance_radius"] = "5"; // miles
		$gas_price_search["type"] = "reg";
		$gas_price_search["sort_by"] = "distance";
		
		$url["live_api"] = "http://api.mygasfeed.com/stations/radius/".$lat."/".$lng."/".$gas_price_search["distance_radius"]."/".$gas_price_search["type"]."/".$gas_price_search["sort_by"]."/".$api_key["gas_feed"].".json";
		
        $request = curl_init(); 
        curl_setopt($request, CURLOPT_URL, $url["live_api"]); 
		curl_setopt($request,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
		
        $response = curl_exec($request); 
        curl_close($request);
		
		$json_response = json_decode($response, true);
		
		if($json_response["status"]["error"] == "NO") {
			if($json_response["stations"][0]["reg_price"] != "N/A") {
				$price = $json_response["stations"][0]["reg_price"];
			} else if($json_response["stations"][1]["reg_price"] != "N/A") {
				$price = $json_response["stations"][1]["reg_price"];
			}
		}
		
		return $price;		
	}

	function get_jobs($search_query) {	
		global $api_key;
		global $home_zipcode;
		
		$url["live_api"] = "https://api.careeronestop.org/v1/jobsearch/tLNNABeC7vjcGDu/".$search_query."/".$home_zipcode[0]."/25/0/0/0/10/60";
		
        $request = curl_init(); 
        curl_setopt($request, CURLOPT_URL, $url["live_api"]); 
		curl_setopt($request,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
		$header = array(
		    'Accept: application/json',
		    'Content-Type: application/json',
		    'Authorization: Bearer a0hRhXIF9d0q9iNPsWSblQK4Mnhbf5MIx1yf+7578BccdpyGTtknJfNYvm4LEcE7H6DNvyjZ+Wpfr12N6vFwfQ=='
		);
	    curl_setopt($request, CURLOPT_HTTPHEADER, $header);
		
        $response = curl_exec($request); 
        curl_close($request);
		
		return json_decode($response, true);
	}
	
	function get_directions_to_job($origin, $destination, $travel_mode) {
		global $api_key;
		$origin = urlencode($origin);
		$destination = urlencode($destination);
		$travel_mode = urlencode($travel_mode);
		
		$url["live_api"] = "https://maps.googleapis.com/maps/api/directions/json?key=".$api_key["google_directions"]."&origin=".$origin."&destination=".$destination."&mode=".$travel_mode;
		$url["local_example_public_transit"] = "http://localhost/job-commute-search/example_json/google_directions_public_transit.json";
		$url["local_example_driving"] = "http://localhost/job-commute-search/example_json/google_directions_driving.json";
		
        $request = curl_init(); 
        curl_setopt($request, CURLOPT_URL, $url["live_api"]); 
		curl_setopt($request,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); 
		
        $response = curl_exec($request); 
        curl_close($request);   
		
		$directions = json_decode($response, true);

		$formatted_directions["end_address"] = $directions["routes"][0]["legs"][0]["end_address"];
		
		if($travel_mode == "transit") {
			$formatted_directions["fare"] = ($directions["routes"][0]["fare"] != "" ? $directions["routes"][0]["fare"]["value"] : "");
			$formatted_directions["time_seconds_value"] = $directions["routes"][0]["legs"][0]["duration"]["value"];
			$formatted_directions["time_formatted"] = $directions["routes"][0]["legs"][0]["duration"]["text"];
		
			foreach($directions["routes"][0]["legs"][0]["steps"] as $steps) {
				$formatted_directions["steps"][] = $steps["html_instructions"];
			}
		} else {
			$formatted_directions["distance"] = $directions["routes"][0]["legs"][0]["distance"];
			$formatted_directions["distance"]["value_miles"] = explode(" ", $directions["routes"][0]["legs"][0]["distance"]["text"])[0];
			$formatted_directions["duration"] = $directions["routes"][0]["legs"][0]["duration"];
		}
		
		return $formatted_directions;
	}
	
	function ratings($home_address, $work_address, $formatted_info) {
		global $home_zipcode;
		// if home and work are same zip code
		preg_match("/\d{5}(-\d{4})?\b/", $work_address, $work_zipcode);
		if($home_zipcode[0] == $work_zipcode[0]) {
			echo "<div class='tag' style='background: #7ED321;'>";
			echo "Same Neighborhood";
			echo "</div>";
		}
		
		if(	$formatted_info["public_transit"]["daily"]["one-way-duration-seconds"] <= 1800 &&
			$formatted_info["public_transit"]["daily"]["number_of_steps"] <= 3 &&
			$formatted_info["public_transit"]["monthly"]["fare-price"]["value"] <= 5
		) {
			echo "<div class='tag' style='background: #50E3C2;'>";
			echo "Good Public Transit Commute";
			echo "</div>";
		}
	}
?>

<!DOCTYPE HTML>
<html>

	<head>
		<meta charset="UTF-8">
		<title>Job Commute Search</title>
		<link rel="stylesheet/less" href="resources/css/style.less">
		<script src="resources/js/lib/less.js"></script>
		<script src="resources/js/lib/jquery.js"></script>
		<script src="resources/js/main.js"></script>
		<meta name="viewport" content="width=device-width, initial-scale=1">
	</head>
	<body>
		<form action="./" method="GET">
			<label>Home Address </label> <input type="text" name="home" value="<?php echo $home_address; ?>" autocomplete="off">
			<br><br>
			<label>Job Category </label> <input type="text" name="job_category" value="<?php echo $_GET["job_category"]; ?>" autocomplete="off">
			<br><br>
			<input type="submit" value="Go">
		</form>
		
		<?php if($_GET["job_category"]) { ?>
			
			<?php
				$geocode_address = geocode_address($home_address);
				$gas_price_per_gallon = get_gas_prices_near_location($geocode_address["results"][0]["geometry"]["location"]["lat"], $geocode_address["results"][0]["geometry"]["location"]["lng"]);
			?>
			<br><br>
			<b>Filters</b>&nbsp;
			<br>
			<br>
			<span style="font-size: 13px;"><b>Public Transit</b><br></span>
			<br>
			<button class="apply-filter-button" data-filter="public-transit-commute-time-less-than-1-hour">Commute Time Under 1 hr</button>
			<button class="apply-filter-button" data-filter="public-transit-commute-time-less-than-30-mins">Commute Time Under 30 min</button>
			<button class="apply-filter-button" data-filter="public-transit-fare-price-less-than-5-dollars">Fare Under $5</button>
			<button class="apply-filter-button" data-filter="public-transit-steps-less-or-equal-to-3">Steps Less or Equal to 3</button>
			<br>
			<br>
			<span style="font-size: 13px;"><b>Driving</b><br></span>
			<br>
			<button class="apply-filter-button" data-filter="driving-monthly-gas-cost-under-50">Monthly Gas Cost Under $50</button>
			<button class="apply-filter-button" data-filter="driving-monthly-gas-cost-under-100">Monthly Gas Cost Under $100</button>
			<button class="apply-filter-button" data-filter="driving-daily-commute-time-under-15-mins">Commute Under 15 mins</button>
			<button class="apply-filter-button" data-filter="driving-daily-commute-time-under-30-mins">Commute Under 30 mins</button>
			<button class="apply-filter-button" data-filter="driving-daily-commute-time-under-60-mins">Commute Under 1 hour</button>
			<br>
			<br>
			<button id="reset-filters-button" style="opacity: 0; pointer-events: none;">✕ Reset Filters</button>
		<?php } ?>
		<br><br>
		
		<?php
			$jobs = get_jobs($_GET["job_category"])["Jobs"];
			
			foreach($jobs as $key => $job_info) {
				$jobs[$key]["public_transit"]["directions"] = get_directions_to_job($home_address, $job_info["Company"]." ".$job_info["Location"], "transit");
				$jobs[$key]["driving"]["directions"] = get_directions_to_job($home_address, $job_info["Company"]." ".$job_info["Location"], "driving");
			}
			
			$number_work_days_per_month = 20;
		?>
		
		<div id="jobs">
		<?php
			foreach($jobs as $job_key => $job_info) {
				// calculate public transit info
				$formatted_info["public_transit"]["daily"]["one-way-duration"] = $job_info["public_transit"]["directions"]["time_formatted"];
				$formatted_info["public_transit"]["daily"]["one-way-duration-seconds"] = $job_info["public_transit"]["directions"]["time_seconds_value"];
				$formatted_info["public_transit"]["daily"]["number-of-steps"] = count($job_info["public_transit"]["directions"]["steps"]);
				$formatted_info["public_transit"]["daily"]["fare-price"]["value"] = $job_info["public_transit"]["directions"]["fare"]; 
				$formatted_info["public_transit"]["daily"]["fare-price"]["text"] = "$".number_format($formatted_info["public_transit"]["daily"]["fare-price"]["value"], 2);
				
				$formatted_info["public_transit"]["monthly"]["duration"] = number_format((($job_info["public_transit"]["directions"]["time_seconds_value"]/60)*2*$number_work_days_per_month)/60, 1);
				$formatted_info["public_transit"]["monthly"]["fare-price"]["value"] = $job_info["public_transit"]["directions"]["fare"]*2*$number_work_days_per_month;
				$formatted_info["public_transit"]["monthly"]["fare-price"]["text"] = "$".number_format($formatted_info["public_transit"]["monthly"]["fare-price"]["value"], 2);
				
				$formatted_info["public_transit"]["yearly"]["duration"] = number_format((($job_info["public_transit"]["directions"]["time_seconds_value"]/60)*2*$number_work_days_per_month*12)/60, 1);
				$formatted_info["public_transit"]["yearly"]["fare-price"]["value"] = $job_info["public_transit"]["directions"]["fare"]*2*$number_work_days_per_month*12;
				$formatted_info["public_transit"]["yearly"]["fare-price"]["text"] = "$".number_format($formatted_info["public_transit"]["yearly"]["fare-price"]["value"], 2);
				
				// calculate driving info
				$average_mpg = 23.6;
				$daily_one_way_cost = ($job_info["driving"]["directions"]["distance"]["value_miles"] / $average_mpg) * $gas_price_per_gallon;
				
				$formatted_info["driving"]["daily"]["one-way-duration"] = $job_info["driving"]["directions"]["duration"]["text"];
				$formatted_info["driving"]["daily"]["one-way-duration-seconds"] = $job_info["driving"]["directions"]["duration"]["value"];
				$formatted_info["driving"]["daily"]["distance"] = $job_info["driving"]["directions"]["distance"]["text"];
				$formatted_info["driving"]["daily"]["cost"]["value"] = $daily_one_way_cost;
				$formatted_info["driving"]["daily"]["cost"]["text"] = "$".number_format($daily_one_way_cost, 2);
				
				$formatted_info["driving"]["monthly"]["duration"] = number_format((($job_info["driving"]["directions"]["duration"]["value"]/60)*2*$number_work_days_per_month)/60, 1);
				$formatted_info["driving"]["monthly"]["distance"] = $job_info["driving"]["directions"]["distance"]["value_miles"]*2*$number_work_days_per_month;
				$formatted_info["driving"]["monthly"]["cost"]["value"] = $daily_one_way_cost*2*$number_work_days_per_month;
				$formatted_info["driving"]["monthly"]["cost"]["text"] = "$".number_format($daily_one_way_cost*2*$number_work_days_per_month, 2);
				
				$formatted_info["driving"]["yearly"]["duration"] = number_format((($job_info["driving"]["directions"]["duration"]["value"]/60)*2*$number_work_days_per_month*12)/60, 1);
				$formatted_info["driving"]["yearly"]["distance"] = $job_info["driving"]["directions"]["distance"]["value_miles"]*2*$number_work_days_per_month*12;
				$formatted_info["driving"]["yearly"]["cost"]["value"] = $daily_one_way_cost*2*$number_work_days_per_month*12;
				$formatted_info["driving"]["yearly"]["cost"]["text"] = "$".number_format($formatted_info["driving"]["yearly"]["cost"]["value"], 2);
		?>
		
			<div
				id="job_<?php echo $job_key; ?>"
				data-filter-public-transit-commute-time-seconds="<?php echo $formatted_info["public_transit"]["daily"]["one-way-duration-seconds"]; ?>"
				data-filter-public-transit-commute-number-of-steps="<?php echo $formatted_info["public_transit"]["daily"]["number-of-steps"] ?>"
				data-filter-public-transit-fare-value="<?php echo $formatted_info["public_transit"]["daily"]["fare-price"]["value"]; ?>"
				data-filter-driving-monthly-gas-cost="<?php echo $formatted_info["driving"]["monthly"]["cost"]["value"]; ?>"
				data-filter-driving-daily-commute-time-seconds="<?php echo $formatted_info["driving"]["daily"]["one-way-duration-seconds"]; ?>"
			>
				<h2><a href="<?php echo $job_info["URL"]; ?>" target="_blank"><?php echo $job_info["JobTitle"]; ?></a></h2>
				<h3><?php echo $job_info["Company"]; ?></h3>
				<p style="margin-top: 7px;"><?php echo $job_info["public_transit"]["directions"]["end_address"]; ?></p>
				<p><?php echo ratings($home_address, $job_info["public_transit"]["directions"]["end_address"], $formatted_info); ?>
				<p>
					<b>🚍 Public Transit</b>
					<br>
					<label class="period">Daily</label> 
					<span class="filter-highlight" data-filter-highlight="filter-public-transit-commute-time-seconds"><?php echo $formatted_info["public_transit"]["daily"]["one-way-duration"]; ?> one way</span> · 
					<span class="filter-highlight" data-filter-highlight="filter-public-transit-commute-number-of-steps"><?php echo $formatted_info["public_transit"]["daily"]["number-of-steps"]; ?> steps</span>
					<?php if($job_info["public_transit"]["directions"]["fare"] != "") { ?> · <span class="filter-highlight" data-filter-highlight="filter-public-transit-fare-value"><?php echo $formatted_info["public_transit"]["daily"]["fare-price"]["text"]; ?></span><?php } ?>
					<br>
					<label class="period">Monthly</label> 
					<?php echo $formatted_info["public_transit"]["monthly"]["duration"]; ?> hours
					<?php if($job_info["public_transit"]["directions"]["fare"] != "") { ?> · <?php echo $formatted_info["public_transit"]["monthly"]["fare-price"]["text"]; } ?>
					<br>
					<label class="period">Yearly</label> 
					<?php echo $formatted_info["public_transit"]["yearly"]["duration"]; ?> hours
					<?php if($job_info["public_transit"]["directions"]["fare"] != "") { ?> · <?php echo $formatted_info["public_transit"]["yearly"]["fare-price"]["text"]; } ?>
				</p>
				<p>
					<b>🚘 Driving</b>
					<br>
					<label class="period">Daily</label> 
					<span class="filter-highlight" data-filter-highlight="filter-driving-daily-commute-time-seconds"><?php echo $formatted_info["driving"]["daily"]["one-way-duration"]; ?> one way</span> · 
					<?php echo $formatted_info["driving"]["daily"]["distance"]; ?> · 
					<?php echo $formatted_info["driving"]["daily"]["cost"]["text"]; ?>
					<br>
					<label class="period">Monthly</label> 
					<?php echo $formatted_info["driving"]["monthly"]["duration"]; ?> hours · 
					<?php echo $formatted_info["driving"]["monthly"]["distance"]; ?> mi · 
					<span class="filter-highlight" data-filter-highlight="filter-driving-monthly-gas-cost"><?php echo $formatted_info["driving"]["monthly"]["cost"]["text"]; ?></span>
					<br>
					<label class="period">Yearly</label> 
					<?php echo $formatted_info["driving"]["yearly"]["duration"]; ?> hours · 
					<?php echo $formatted_info["driving"]["yearly"]["distance"]; ?> mi · 
					<?php echo $formatted_info["driving"]["yearly"]["cost"]["text"]; ?>
				</p>
				<?php /*
				<p style="color: rgba(0, 0, 0, 0.5);">
					<?php
						$index = 0;
						foreach($job_info["public_transit"]["directions"]["steps"] as $step) {
							$index++;
					?>
						<?php echo $index; ?>. <?php echo $step; ?><br>
					<?php } ?>
				</p>
				<?php */ ?>
				<br>
			</div>
			
		<?php } ?>
		</div>
	
	</body>
</html>