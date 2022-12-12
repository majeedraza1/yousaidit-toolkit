const MINUTE_IN_MILLISECONDS = 1000 * 60;
const recordingTimeMS = 5 * MINUTE_IN_MILLISECONDS;

const formatBytes = (x: number) => {
	const units = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
	const k = 1024
	let l = 0, n = x || 0;
	while (n >= k && ++l) {
		n = n / k;
	}
	return (n.toFixed(n < 10 && l > 0 ? 1 : 0) + ' ' + units[l]);
}

const wait = (delayInMS: number) => {
	return new Promise((resolve) => setTimeout(resolve, delayInMS));
}

function log(msg: undefined | string) {
	window.console.log(msg);
}

const startRecording = (stream: MediaStream, lengthInMS: number) => {
	let recorder = new MediaRecorder(stream);
	let data: Blob[] = [];

	recorder.ondataavailable = (event: BlobEvent) => data.push(event.data as Blob);
	recorder.start();
	log(`${recorder.state} for ${lengthInMS / 1000} seconds…`);

	recorder.addEventListener('stop', () => {
		return new Promise(resolve => {
			log('Media recorder has been stopped.');
			resolve(data)
		})
	});

	let stopped = new Promise((resolve, reject) => {
		recorder.addEventListener('stop', (event: Event) => {
			log('Media recorder has been stopped.');
			resolve(event)
		});
		recorder.onerror = (event: Event) => {
			log('Media recorder has an error');
			reject(event)
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

const stopRecording = (stream: MediaStream) => {
	stream.getTracks().forEach((track: MediaStreamTrack) => track.stop());
}

const initRecording = () => {
	let preview = document.querySelector('#video-recording-preview') as HTMLVideoElementWithCaptureStream;
	let recording = document.querySelector('#video-recording') as HTMLVideoElementWithCaptureStream;

	navigator.mediaDevices.getUserMedia({
		video: {
			width: {ideal: 1280},
			height: {ideal: 720},
			frameRate: {ideal: 15, max: 30}
		},
		audio: true
	}).then((stream) => {
		preview.srcObject = stream;
		// preview.captureStream = preview.captureStream || preview.mozCaptureStream;
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
	initRecording,
	stopRecording
}
