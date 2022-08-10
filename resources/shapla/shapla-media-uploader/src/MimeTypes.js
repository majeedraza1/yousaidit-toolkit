class MimeTypes {
	/**
	 * Class constructor
	 */
	constructor() {
		this.mime_types = {
			...this.image_formats(),
			...this.video_formats(),
			...this.audio_formats(),
			...this.text_formats(),
			...this.general_file_formats(),
		};
	}

	/**
	 * Get all mime types
	 *
	 * @returns {Object}
	 */
	get_mime_types() {
		return this.mime_types;
	}

	/**
	 * Get file extensions
	 *
	 * @param formats {Object}
	 * @returns {(string)[]}
	 */
	get_file_extensions(formats) {
		let keys = Object.keys(formats);
		let extensions = [];
		keys.forEach(key => {
			extensions.push(...key.split('|'))
		});
		return extensions;
	}

	/**
	 * Image mime types
	 *
	 * @return {Object}
	 */
	image_formats() {
		return {
			'jpg|jpeg|jpe': 'image/jpeg',
			'gif': 'image/gif',
			'png': 'image/png',
			'bmp': 'image/bmp',
			'tiff|tif': 'image/tiff',
			'ico': 'image/x-icon',
		}
	}

	/**
	 * Image mime types
	 *
	 * @returns {(string)[]}
	 */
	image_mime_types() {
		return Object.values(this.image_formats());
	}

	/**
	 * Video mime types
	 *
	 * @returns {Object}
	 */
	video_formats() {
		return {
			'asf|asx': 'video/x-ms-asf',
			'wmv': 'video/x-ms-wmv',
			'wmx': 'video/x-ms-wmx',
			'wm': 'video/x-ms-wm',
			'avi': 'video/avi',
			'divx': 'video/divx',
			'flv': 'video/x-flv',
			'mov|qt': 'video/quicktime',
			'mpeg|mpg|mpe': 'video/mpeg',
			'mp4|m4v': 'video/mp4',
			'ogv': 'video/ogg',
			'webm': 'video/webm',
			'mkv': 'video/x-matroska',
			'3gp|3gpp': 'video/3gpp',  // Can also be audio.
			'3g2|3gp2': 'video/3gpp2', // Can also be audio.
		}
	}

	/**
	 * Audio mime types
	 *
	 * @returns {Object}
	 */
	audio_formats() {
		return {
			'mp3|m4a|m4b': 'audio/mpeg',
			'aac': 'audio/aac',
			'ra|ram': 'audio/x-realaudio',
			'wav': 'audio/wav',
			'ogg|oga': 'audio/ogg',
			'flac': 'audio/flac',
			'mid|midi': 'audio/midi',
			'wma': 'audio/x-ms-wma',
			'wax': 'audio/x-ms-wax',
			'mka': 'audio/x-matroska',
		}
	}

	/**
	 * Text mime types
	 * @returns {Object}
	 */
	text_formats() {
		return {
			'txt|asc|c|cc|h|srt': 'text/plain',
			'csv': 'text/csv',
			'tsv': 'text/tab-separated-values',
			'ics': 'text/calendar',
			'rtx': 'text/richtext',
			'css': 'text/css',
			'htm|html': 'text/html',
			'vtt': 'text/vtt',
			'dfxp': 'application/ttaf+xml',
		}
	}

	/**
	 * General file formats
	 *
	 * @returns {Object}
	 */
	general_file_formats() {
		return {
			// Misc application formats.
			'js': 'application/javascript',
			'pdf': 'application/pdf',
			'class': 'application/java',
			'tar': 'application/x-tar',
			'zip': 'application/zip',
			'gz|gzip': 'application/x-gzip',
			'rar': 'application/rar',
			'7z': 'application/x-7z-compressed',
			'psd': 'application/octet-stream',
			'xcf': 'application/octet-stream', // GIMP file format
			// MS Office formats.
			'doc': 'application/msword',
			'pot|pps|ppt': 'application/vnd.ms-powerpoint',
			'wri': 'application/vnd.ms-write',
			'xla|xls|xlt|xlw': 'application/vnd.ms-excel',
			'mdb': 'application/vnd.ms-access',
			'mpp': 'application/vnd.ms-project',
			'docx': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'docm': 'application/vnd.ms-word.document.macroEnabled.12',
			'dotx': 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
			'dotm': 'application/vnd.ms-word.template.macroEnabled.12',
			'xlsx': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xlsm': 'application/vnd.ms-excel.sheet.macroEnabled.12',
			'xlsb': 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
			'xltx': 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'xltm': 'application/vnd.ms-excel.template.macroEnabled.12',
			'xlam': 'application/vnd.ms-excel.addin.macroEnabled.12',
			'pptx': 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'pptm': 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
			'ppsx': 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'ppsm': 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
			'potx': 'application/vnd.openxmlformats-officedocument.presentationml.template',
			'potm': 'application/vnd.ms-powerpoint.template.macroEnabled.12',
			'ppam': 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
			'sldx': 'application/vnd.openxmlformats-officedocument.presentationml.slide',
			'sldm': 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
			'onetoc|onetoc2|onetmp|onepkg': 'application/onenote',
			'oxps': 'application/oxps',
			'xps': 'application/vnd.ms-xpsdocument',
			// OpenOffice formats.
			'odt': 'application/vnd.oasis.opendocument.text',
			'odp': 'application/vnd.oasis.opendocument.presentation',
			'ods': 'application/vnd.oasis.opendocument.spreadsheet',
			'odg': 'application/vnd.oasis.opendocument.graphics',
			'odc': 'application/vnd.oasis.opendocument.chart',
			'odb': 'application/vnd.oasis.opendocument.database',
			'odf': 'application/vnd.oasis.opendocument.formula',
			// WordPerfect formats.
			'wp|wpd': 'application/wordperfect',
			// iWork formats.
			'key': 'application/vnd.apple.keynote',
			'numbers': 'application/vnd.apple.numbers',
			'pages': 'application/vnd.apple.pages',
		}
	}
}

export {MimeTypes}
export default MimeTypes;
