/**
 * Determines the difference between two timestamps.
 * The difference is returned in a human readable format such as "1 hour", "5 minutes", "2 days".
 *
 * @param {string} from - ISO date string but can accept any valid javaScript date
 * @param {string|null} to - ISO date string but can accept any valid javaScript date
 *
 * @return {string}
 */
function human_time_diff(from, to = null) {
	if (!to) {
		to = (new Date).toISOString()
	}

	let date1 = (new Date(from)).getTime() / 1000,
		date2 = (new Date(to)).getTime() / 1000,
		diff = Math.round(Math.abs(date2 - date1));

	let MINUTE_IN_SECONDS = 60,
		HOUR_IN_SECONDS = (MINUTE_IN_SECONDS * 60),
		DAY_IN_SECONDS = (24 * HOUR_IN_SECONDS),
		WEEK_IN_SECONDS = (7 * DAY_IN_SECONDS),
		MONTH_IN_SECONDS = (30 * DAY_IN_SECONDS),
		YEAR_IN_SECONDS = (365 * DAY_IN_SECONDS);

	if (diff < MINUTE_IN_SECONDS) {
		let secs = diff;
		if (secs <= 1) {
			secs = 1;
		}
		return secs > 1 ? `${secs} seconds` : `${secs} second`;
	}

	if (diff < HOUR_IN_SECONDS && diff >= MINUTE_IN_SECONDS) {
		let minutes = Math.round(diff / MINUTE_IN_SECONDS);
		if (minutes <= 1) {
			minutes = 1;
		}
		return minutes > 1 ? `${minutes} minutes` : `${minutes} minute`;
	}

	if (diff < DAY_IN_SECONDS && diff >= HOUR_IN_SECONDS) {
		let $hours = Math.round(diff / HOUR_IN_SECONDS);
		if ($hours <= 1) {
			$hours = 1;
		}
		return $hours > 1 ? `${$hours} hours` : `${$hours} hour`;
	}

	if (diff < WEEK_IN_SECONDS && diff >= DAY_IN_SECONDS) {
		let $days = Math.round(diff / DAY_IN_SECONDS);
		if ($days <= 1) {
			$days = 1;
		}
		return $days > 1 ? `${$days} days` : `${$days} day`;
	}

	if (diff < MONTH_IN_SECONDS && diff >= WEEK_IN_SECONDS) {
		let $weeks = Math.round(diff / WEEK_IN_SECONDS);
		if ($weeks <= 1) {
			$weeks = 1;
		}
		return $weeks > 1 ? `${$weeks} weeks` : `${$weeks} week`;
	}

	if (diff < YEAR_IN_SECONDS && diff >= MONTH_IN_SECONDS) {
		let $months = Math.round(diff / MONTH_IN_SECONDS);
		if ($months <= 1) {
			$months = 1;
		}
		return $months > 1 ? `${$months} months` : `${$months} month`;
	}

	if (diff >= YEAR_IN_SECONDS) {
		let $years = Math.round(diff / YEAR_IN_SECONDS);
		if ($years <= 1) {
			$years = 1;
		}
		return $years > 1 ? `${$years} years` : `${$years} year`;
	}

	return 'Invalid date';
}

export {human_time_diff}
export default human_time_diff;
