import sys
try:
    from PyPDF2 import PdfReader
    reader = PdfReader(sys.argv[1])
    text = ''
    for page in reader.pages:
        t = page.extract_text()
        if t: text += t + ' '
    print(text)
except Exception as e:
    pass
