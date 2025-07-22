from flask import Flask, render_template, request, send_file
from PIL import Image, ImageDraw, ImageFont
import io

app = Flask(__name__)

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/generate', methods=['POST'])
def generate_signature():
    nome = request.form['nome']
    setor = request.form['setor']
    ramal = request.form['ramal']

    # Create a blank image
    width, height = 400, 120
    img = Image.new('RGB', (width, height), color = 'white')
    d = ImageDraw.Draw(img)

    # Load font
    try:
        font = ImageFont.truetype("arial.ttf", 15)
        font_bold = ImageFont.truetype("arialbd.ttf", 15)
    except IOError:
        font = ImageFont.load_default()
        font_bold = ImageFont.load_default()


    # Draw text
    d.text((10,10), nome, font=font_bold, fill=(0,0,0))
    d.text((10,35), setor, font=font, fill=(0,0,0))
    d.text((10,60), "Ramal: " + ramal, font=font, fill=(0,0,0))

    # Add logo
    try:
        logo = Image.open("app/static/logo.png").convert("RGBA")
        logo = logo.resize((80, 80))
        img.paste(logo, (310, 20), logo)
    except FileNotFoundError:
        # Draw a placeholder rectangle if logo not found
        d.rectangle([310, 20, 390, 100], fill="gray", outline="black")
        d.text((320, 50), "Logo", font=font, fill="white")


    # Save image to a byte stream
    byte_io = io.BytesIO()
    img.save(byte_io, 'JPEG')
    byte_io.seek(0)

    return send_file(byte_io, mimetype='image/jpeg')

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)
