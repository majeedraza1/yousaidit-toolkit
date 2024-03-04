const colors = [
  {hex: '#1D1D1B', label: 'Black'},
  {hex: '#706F6F', label: 'Gray'},
  {hex: '#E6007E', label: 'Pink'},
  {hex: '#36A9E1', label: ''},
  {hex: '#312783', label: 'Blue'},
  {hex: '#009640', label: 'Green'},
  {hex: '#FFDE00', label: 'Yellow'},
  {hex: '#E30613', label: 'Red'},
  {hex: '#DF91BE', label: ''},
  {hex: '#F39200', label: 'Orange'},
];
const fontFamilies = window.YousaiditFontsList || [{label: "Open Sans", fontFamily: "Open Sans"}];



const font_sizes = ['12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40']
const alignments = [
  {label: 'Left', value: 'left'},
  {label: 'Center', value: 'center'},
  {label: 'Right', value: 'right'},
]

export {
  colors,
  fontFamilies,
  font_sizes,
  alignments
}