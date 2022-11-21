const recordingTimeMS = 5000;

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

	let stopped = new Promise((resolve, reject) => {
		recorder.onstop = resolve;
		recorder.onerror = (event) => reject(event.name);
	});

	let recorded = wait(lengthInMS).then(
		() => {
			if (recorder.state === "recording") {
				recorder.stop();
			}
		},
	);

	return Promise.all([
		stopped,
		recorded
	])
		.then(() => data);
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
	}).then(() => startRecording(preview.captureStream(), recordingTimeMS))
		.then((recordedChunks) => {
			let recordedBlob = new Blob(recordedChunks, {type: "video/webm"});
			recording.src = URL.createObjectURL(recordedBlob);

			log(`Successfully recorded ${recordedBlob.size} bytes of ${recordedBlob.type} media.`);
		})
		.catch((error) => {
			if (error.name === "NotFoundError") {
				log("Camera or microphone not found. Can't record.");
			} else {
				log(error);
			}
		});
}

export {
	wait,
	startRecording,
	initRecording,
	stopRecording
}
