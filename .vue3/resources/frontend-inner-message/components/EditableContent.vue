<template>
  <div ref="root" class="editable-content-container" :style="containerStyle">
    <div class="editable-content">
      <div class="editable-content__editor"
           :style="editorStyle"
           :contenteditable="state.isEditable?'true':'false'"
           @focus="handleFocusEvent"
           @input="handleInputEvent"
      />
    </div>
  </div>
</template>

<script lang="ts" setup>
import {
  calculateElementHeight,
  calculateElementPadding,
  calculateFontSizeScale,
  cardSizeFromName
} from '../../utils/helper.ts'
import {computed, onMounted, reactive, ref, watch} from "vue";

const root = ref<HTMLDivElement>(null)

const props = defineProps({
  openAiEditable: {type: Boolean, default: false},
  editable: {type: Boolean, default: true},
  value: {type: String},
  placeholder: {type: String, default: ''},
  cardSize: {type: String, default: 'square'},
  fontFamily: {type: String,},
  fontSize: {type: [Number,String], default: 12},
  textAlign: {type: String, default: 'center'},
  color: {type: String, default: '#000'}
})

const state = reactive({
  text: '',
  canvas_height: 0,
  canvas_width: 0,
  canvas_padding: 0,
  cardSizes: [],
  editableContent: null,
  editableContentEditor: null,
  showLengthError: false,
  isEditable: false,
  showPlaceholder: true,
})

const emit = defineEmits<{
  input: [value: string]
  lengthError: [value: boolean]
}>()

const containerStyle = computed<Record<string, string>[]>(() => {
  let styles = [];
  styles.push({'height': `${state.canvas_height}px`});
  return styles;
})
const editorStyle = computed<Record<string, string>[]>(() => {
  let styles = [];
  if (props.fontFamily) {
    styles.push({'--font-family': props.fontFamily});
  }
  if (props.fontSize) {
    let fontSize = calculateFontSizeScale(state.cardSizes[0] / 2, state.canvas_width, props.fontSize);
    styles.push({'--font-size': `${fontSize}pt`});
  }
  if (props.textAlign) {
    styles.push({'--text-align': props.textAlign});
  }
  if (props.color) {
    styles.push({'--color': props.color});
  }
  return styles;
})


const handleFocusEvent = (event: FocusEvent) => {
  let text = (event.target as HTMLDivElement).innerHTML;
  if (text.indexOf(props.placeholder) !== -1) {
    (event.target as HTMLDivElement).innerHTML = '';
  }
}

const handleInputEvent = (event: InputEvent) => {
  state.text = (event.target as HTMLDivElement).innerHTML;
  state.showLengthError = state.editableContentEditor.offsetHeight > (0.90 * state.editableContent.offsetHeight);
  emit('input', state.text);
}
const calculate_canvas_dimension = () => {
  state.cardSizes = cardSizeFromName(props.cardSize);
  state.canvas_height = calculateElementHeight(props.cardSize, root.value);
  state.canvas_width = root.value.offsetWidth;
}
const calculate_canvas_edge_padding = () => {
  state.canvas_padding = calculateElementPadding(state.cardSizes[0] / 2, state.canvas_width);
}
const updateTextAndPlaceholder = () => {
  const lines = props.value
      // .replace('<div class="editable-content__html">', '')
      .split('<div>')
      .map(_text => _text.replace('</div>', ''))
      .filter(line => typeof line === 'string' && line.length);
  if (lines.length) {
    state.showPlaceholder = false;
    let contentEl = root.value.querySelector('.editable-content__editor')
    contentEl.innerHTML = '';
    lines.forEach(line => {
      let divEl = document.createElement('div');
      if ('<br>' === line) {
        divEl.append(document.createElement('br'))
      } else {
        divEl.innerText = line;
      }
      contentEl.append(divEl);
    })
  } else {
    let contentEl = root.value.querySelector('.editable-content__editor')
    let divEl = document.createElement('div');
    divEl.innerText = props.placeholder;
    contentEl.append(divEl);
  }
}
const updateFromMessageLines = (lines: string[]) => {
  state.showPlaceholder = false;
  let contentEl = root.value.querySelector('.editable-content__editor')
  contentEl.innerHTML = '';
  lines.forEach(line => {
    let divEl = document.createElement('div');
    if (['<br>'].includes(line)) {
      divEl.append(document.createElement('br'))
    } else {
      divEl.innerText = line;
    }
    contentEl.append(divEl);
  })
  setTimeout(() => emit('input', contentEl.innerHTML), 100)
}

watch(() => props.value, newValue => state.text = newValue);
watch(() => props.editable, newValue => setTimeout(() => state.isEditable = newValue, 500));
watch(() => state.showLengthError, newValue => emit('lengthError', newValue));

onMounted(() => {

  document.execCommand("defaultParagraphSeparator", false, "div");
  state.text = props.value;
  setTimeout(() => {
    calculate_canvas_dimension();
    calculate_canvas_edge_padding();

    state.editableContent = root.value.querySelector('.editable-content');
    state.editableContentEditor = root.value.querySelector('.editable-content__editor');
    state.isEditable = props.editable;
    updateTextAndPlaceholder();

    state.editableContentEditor.addEventListener('paste', (event: ClipboardEvent) => {
      event.preventDefault();
      // get the plain text value of the clipboard
      let text = (event.clipboardData || window.clipboardData).getData('text/plain');
      // replace any HTML tags in the text value with their corresponding entities
      text = text.replace(/<\/?[^>]+(>|$)/g, "");
      text = text.replace(/(?:\r\n|\r|\n)/g, "<br>");
      text = text.replace(/\n/g, "<br>");
      let lines = text.split('<br>');

      let contentEl = document.createElement('div')
      lines.forEach((line: string) => {
        if (line.length) {
          let divEl = document.createElement('div');
          divEl.innerText = line;
          contentEl.append(divEl);
        }
      })

      // insert the modified text value into the textarea
      document.execCommand("insertHTML", false, contentEl.innerHTML);
    })
  }, 100);


  document.addEventListener('openAiContent', (event: CustomEvent) => {
    if (props.openAiEditable) {
      updateFromMessageLines(event.detail.lines);
    }
  })
})
</script>
