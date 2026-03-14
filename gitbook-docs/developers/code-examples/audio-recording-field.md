---
description: >-
  WordPress form with microphone recording field. Allow your user to record
  audio and upload it as a audio file (webm). User can preview the recording and
  start a new recording if needed.
---

# Audio Recording Field

Live demo: [https://super-forms.com/microphone-recording/](https://super-forms.com/microphone-recording/)

The JavaScript code will replace a regular file upload element with a "Start recording" button, which allows users to record voice via the browser by allowing to record their microphone.

For this to work, you will require custom JavaScript code (until we build a dedicated field or option to record audio). The JavaScript code will convert an existing File upload element into a "Record audio" element. It is required to name the file upload field `audio_recording` otherwise the JavaScript code won't function and nothing would happen.

To get the demo form up and running, simply copy paste the Form Elements code below under your \[CODE] tab on the builder page. And make sure you place the JavaScript code on the same page where your form is located.

```
[
    {
        "tag": "text",
        "group": "form_elements",
        "data": {
            "name": "email",
            "email": "E-mail address:",
            "placeholder": "Your E-mail Address",
            "placeholderFilled": "E-mail Address",
            "type": "email",
            "validation": "email",
            "icon": "envelope;far"
        }
    },
    {
        "tag": "file",
        "group": "form_elements",
        "data": {
            "name": "audio_recording",
            "email": "File:",
            "extensions": "jpg|jpeg|png|gif|pdf|webm",
            "class": "f4d-audio-recording",
            "wrapper_class": "f4d-audio-recording-wrapper",
            "icon": "download"
        }
    }
]
```

JavaScript code (place this on the page where your form will be located.)

```
<script> 
(function () {
  function initAudioRecorder() {
    // Your audio logic here
    console.log("DOM ready – recorder initialized");
	  debugger;
	  const form = document.querySelector(".super-form");
	  if (!form) return;
	
		var hideFileUpload = document.querySelector('.f4d-audio-recording');
		if(hideFileUpload) hideFileUpload.style.display = 'none';
	  const recordBtn = document.createElement("button");
	  recordBtn.type = "button";
	  recordBtn.textContent = "🎙️ Start Recording";
	  recordBtn.style.margin = "10px 0";
	
	  const deleteBtn = document.createElement("button");
	  deleteBtn.type = "button";
	  deleteBtn.textContent = "❌ Delete Recording";
	  deleteBtn.style.display = "none";
	
	  //const audioPreview = document.createElement("audio");
	  //audioPreview.controls = true;
	  //audioPreview.style.display = "none";
	
	  // Find the Super Forms file input by name
	  const fileInput = form.querySelector('input[name="audio_recording"]').parentNode.querySelector('input[name="files[]"]');
	  let mediaRecorder;
	  let audioChunks = [];
	
	  recordBtn.addEventListener("click", async () => {
	    if (mediaRecorder && mediaRecorder.state === "recording") {
	      mediaRecorder.stop();
	      recordBtn.textContent = "🎙️ Start Recording";
	    } else {
	      try {
	        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
	        mediaRecorder = new MediaRecorder(stream);
	        audioChunks = [];
	
	        mediaRecorder.ondataavailable = (e) => {
	          if (e.data.size > 0) audioChunks.push(e.data);
	        };
				
			mediaRecorder.onstop = () => {
			  debugger;
			  const audioBlob = new Blob(audioChunks, { type: "audio/webm" });
			  const audioFile = new File([audioBlob], "recording.webm", { type: "audio/webm" });
			
			  const audioURL = URL.createObjectURL(audioBlob);
			
			  // STEP 1: Get form ID
				debugger;
				var fileInput = form.querySelector('input[name="audio_recording"]').parentNode.querySelector('input[name="files[]"]');
				const formEl = fileInput.closest(".super-form");
			  const formID = formEl.querySelector('input[name="hidden_form_id"]').value;
			  // STEP 2: Create Super Forms file entry
			  /*const fileData = {
			    name: audioFile.name,
			    size: audioFile.size,
			    type: audioFile.type,
			    file: audioFile,
			    src: audioURL
			  };*/
			
			  if (typeof SUPER.files === "undefined") SUPER.files = {};
			  if (typeof SUPER.files[formID] === "undefined") SUPER.files[formID] = {};
			  //SUPER.files[formID]["audio_recording"] = [fileData]; // name = your field name
			  SUPER.files[formID]["audio_recording"] = [audioFile];

			  // STEP 3: Update the preview (already done correctly)
			  const wrapper = fileInput.closest('.super-fileupload');
			  const fileListContainer = wrapper.parentNode.querySelector('.super-fileupload-files');
			  fileListContainer.innerHTML = "";
			
			  const fileDiv = document.createElement("div");
			  fileDiv.className = "super-uploaded";
			  fileDiv.setAttribute("data-name", "recording.webm");
			  fileDiv.setAttribute("title", "recording.webm");
			  fileDiv.setAttribute("data-type", "audio/webm");
			
			  const spanImg = document.createElement("span");
			  spanImg.className = "super-fileupload-image super-file-type-audio-webm";
			  spanImg.style.width = "180px";
			  spanImg.style.maxWidth = "180px";
			  const audioTag = document.createElement("audio");
			  audioTag.controls = true;
			  audioTag.src = audioURL;
			  spanImg.appendChild(audioTag);
			
			  const info = document.createElement("span");
			  info.className = "super-fileupload-info";
			
			  const fileName = document.createElement("span");
			  fileName.className = "super-fileupload-name";
			  fileName.textContent = "recording.webm";
			
			  const fileDelete = document.createElement("span");
			  fileDelete.className = "super-fileupload-delete";
			  fileDelete.style.cursor = "pointer";
			  fileDelete.title = "Delete";
			
			  fileDelete.addEventListener("click", () => {
			    fileListContainer.innerHTML = "";
			    fileInput.value = "";
			    deleteBtn.style.display = "none";
			    recordBtn.textContent = "🎙️ Start Recording";
			
			    // Clear from SUPER.files
			    if (SUPER.files?.[formID]?.["audio_recording"]) {
			      delete SUPER.files[formID]["audio_recording"];
			    }
			  });
			
			  info.appendChild(fileName);
			  info.appendChild(fileDelete);
			
			  fileDiv.appendChild(spanImg);
			  fileDiv.appendChild(info);
			
			  fileListContainer.appendChild(fileDiv);
			};


	
	        mediaRecorder.start();
	        recordBtn.textContent = "🛑 Stop Recording";
	      } catch (err) {
	        alert("Microphone access denied.");
	      }
	    }
	  });
	
	  deleteBtn.addEventListener("click", () => {
	    audioChunks = [];
	    //audioPreview.src = "";
	    //audioPreview.style.display = "none";
	    fileInput.value = ""; // Clear file input
	    deleteBtn.style.display = "none";
	    recordBtn.textContent = "🎙️ Start Recording";
	  });
	
	  // Insert into form (above the file field)
	  fileInput.parentElement.insertBefore(recordBtn, fileInput);
	  fileInput.parentElement.insertBefore(deleteBtn, fileInput);
	  //fileInput.parentElement.insertBefore(audioPreview, fileInput);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAudioRecorder);
  } else {
    // DOM already ready
    initAudioRecorder();
  }
})();

</script>
```
