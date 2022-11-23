const MINUTE_IN_MILLISECONDS = 1000 * 60;
const recordingTimeMS = 5 * MINUTE_IN_MILLISECONDS;

function formatBytes(x) {
	const units = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
	const k = 1024
	let l = 0, n = parseInt(x, 10) || 0;
	while (n >= k && ++l) {
		n = n / k;
	}
	return (n.toFixed(n < 10 && l > 0 ? 1 : 0) + ' ' + units[l]);
}

function wait(delayInMS) {
	return new Promise((resolve) => setTimeout(resolve, delayInMS));
}

function log(msg) {
	window.console.log(msg);
}

function startRecording(stream, lengthInMS) {
	let recorder = new MediaRecorder(stream);
	let data = [];

	recorder.ondataavailable = (event) => data.push(event.data);
	recorder.start();
	log(`${recorder.state} for ${lengthInMS / 1000} seconds…`);

	recorder.addEventListener('stop', () => {
		return new Promise(resolve => {
			log('Media recorder has been stopped.');
			resolve(data)
		})
	});

	let stopped = new Promise((resolve, reject) => {
		recorder.addEventListener('stop', (event) => {
			log('Media recorder has been stopped.');
			resolve(event)
		});
		recorder.onerror = (event) => {
			log('Media recorder has an error' + event.name);
			reject(event.name)
		};
	});

	let recorded = wait(lengthInMS).then(() => {
		if (recorder.state === "recording") {
			recorder.stop();
			log('Media recorder has been complete.');
		}
	});

	return Promise.all([
		stopped
	]).then(() => data);
}

function stopRecording(stream) {
	stream.getTracks().forEach((track) => track.stop());
}

const initRecording = () => {
	let preview = document.querySelector('#video-recording-preview');
	let recording = document.querySelector('#video-recording');

	navigator.mediaDevices.getUserMedia({
		video: {
			width: {ideal: 1280},
			height: {ideal: 720},
			frameRate: {ideal: 15, max: 30}
		},
		audio: true
	}).then((stream) => {
		preview.srcObject = stream;
		preview.captureStream = preview.captureStream || preview.mozCaptureStream;
		return new Promise((resolve) => preview.onplaying = resolve);
	}).then(() => {
		startRecording(preview.captureStream(), recordingTimeMS)
			.then((recordedChunks) => {
				let recordedBlob = new Blob(recordedChunks, {type: "video/webm"});
				recording.src = URL.createObjectURL(recordedBlob);

				document.dispatchEvent(new CustomEvent('recordComplete', {detail: recordedBlob}));

				log(`Successfully recorded ${formatBytes(recordedBlob.size)} of ${recordedBlob.type} media.`);
			})
			.catch((error) => {
				if (error.name === "NotFoundError") {
					log("Camera or microphone not found. Can't record.");
				} else {
					log(error);
				}
			})
	})
}

export {
	wait,
	startRecording,
	initRecording,
	stopRecording
}
