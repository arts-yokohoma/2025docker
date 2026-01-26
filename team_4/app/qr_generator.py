#!/usr/bin/env python3
import qrcode
import sys
import json
import os
from PIL import Image
import base64

def generate_qr_code(text, output_path='qr_code.png'):
    """Generate a QR code from text and save as image"""
    try:
        # Create QR code instance
        qr = qrcode.QRCode(
            version=1,
            error_correction=qrcode.constants.ERROR_CORRECT_L,
            box_size=10,
            border=4,
        )
        
        # Add data
        qr.add_data(text)
        qr.make(fit=True)
        
        # Create QR code image
        img = qr.make_image(fill_color="#d19758", back_color="#ffffff")
        
        # Add pizza logo overlay
        add_pizza_logo(img)
        
        # Save image
        img.save(output_path)
        
        # Convert to base64 for direct embedding
        with open(output_path, "rb") as image_file:
            base64_image = base64.b64encode(image_file.read()).decode('utf-8')
        
        # Clean up temporary file
        if os.path.exists(output_path):
            os.remove(output_path)
            
        return base64_image
        
    except Exception as e:
        print(f"Error generating QR code: {e}")
        return None

def add_pizza_logo(img):
    """Add a small pizza logo to the center of QR code"""
    try:
        # Convert to RGBA for overlay
        img = img.convert("RGBA")
        width, height = img.size
        
        # Calculate center position
        center_x = width // 2
        center_y = height // 2
        logo_size = 40
        
        # Create pizza logo
        from PIL import ImageDraw
        draw = ImageDraw.Draw(img)
        
        # Draw white background circle
        draw.ellipse(
            [center_x - logo_size//2 - 4, center_y - logo_size//2 - 4,
             center_x + logo_size//2 + 4, center_y + logo_size//2 + 4],
            fill="#ffffff"
        )
        
        # Draw pizza circle (brown)
        draw.ellipse(
            [center_x - logo_size//2, center_y - logo_size//2,
             center_x + logo_size//2, center_y + logo_size//2],
            fill="#d19758"
        )
        
        # Draw cheese dots (yellow)
        for i in range(8):
            angle = (360 / 8) * i
            import math
            x = center_x + int(math.cos(math.radians(angle)) * 15)
            y = center_y + int(math.sin(math.radians(angle)) * 15)
            draw.ellipse([x-3, y-3, x+3, y+3], fill="#ffcc00")
        
        return img
        
    except Exception as e:
        print(f"Error adding logo: {e}")
        return img

def main():
    """Main function to handle HTTP request or command line"""
    try:
        # Get data from stdin (from PHP)
        if not sys.stdin.isatty():
            data = sys.stdin.read()
            params = json.loads(data)
            
            qr_text = params.get('text', '')
            output_format = params.get('format', 'base64')
            
            if not qr_text:
                print(json.dumps({'error': 'No text provided'}))
                return
                
            if output_format == 'base64':
                base64_image = generate_qr_code(qr_text)
                if base64_image:
                    print(json.dumps({
                        'success': True,
                        'image': f"data:image/png;base64,{base64_image}"
                    }))
                else:
                    print(json.dumps({'error': 'Failed to generate QR code'}))
            else:
                # Save to file and return path
                filename = params.get('filename', 'qr_code.png')
                generate_qr_code(qr_text, filename)
                print(json.dumps({
                    'success': True,
                    'filepath': filename
                }))
                
        else:
            # Command line usage
            if len(sys.argv) > 1:
                qr_text = sys.argv[1]
                generate_qr_code(qr_text, 'qr_code.png')
                print("QR code generated: qr_code.png")
            else:
                print("Usage: python generate_qr.py 'text to encode'")
                
    except Exception as e:
        print(json.dumps({'error': str(e)}))

if __name__ == "__main__":
    main()