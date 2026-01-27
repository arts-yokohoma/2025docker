#!/usr/bin/env python3
import qrcode
import sys
import json
import os
from PIL import Image
import base64
import math

def generate_qr_code(text):
    """Generate a QR code from text and return base64 string"""
    try:
        print(f"DEBUG: Generating QR for text length: {len(text)}", file=sys.stderr)
        
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
        
        # Convert to PIL Image for editing
        img = img.convert("RGB")
        
        # Save to bytes instead of file
        from io import BytesIO
        buffer = BytesIO()
        img.save(buffer, format="PNG")
        
        # Get base64 string
        img_bytes = buffer.getvalue()
        base64_image = base64.b64encode(img_bytes).decode('utf-8')
        
        print(f"DEBUG: QR generated successfully, base64 length: {len(base64_image)}", file=sys.stderr)
        return base64_image
        
    except Exception as e:
        print(f"ERROR in generate_qr_code: {str(e)}", file=sys.stderr)
        return None

def main():
    """Main function to handle HTTP request or command line"""
    try:
        # Read from stdin
        if not sys.stdin.isatty():
            data = sys.stdin.read().strip()
            print(f"DEBUG: Received data: {data[:100]}...", file=sys.stderr)
            
            if not data:
                print(json.dumps({'error': 'No data provided'}))
                return
                
            try:
                params = json.loads(data)
                qr_text = params.get('text', '')
                
                if not qr_text:
                    print(json.dumps({'error': 'No text provided'}))
                    return
                    
                base64_image = generate_qr_code(qr_text)
                if base64_image:
                    result = {
                        'success': True,
                        'image': f"data:image/png;base64,{base64_image}"
                    }
                    print(json.dumps(result))
                else:
                    print(json.dumps({'error': 'Failed to generate QR code'}))
                    
            except json.JSONDecodeError as e:
                print(f"ERROR: Invalid JSON: {str(e)}", file=sys.stderr)
                print(json.dumps({'error': f'Invalid JSON: {str(e)}'}))
                
        else:
            # Command line usage
            if len(sys.argv) > 1:
                qr_text = sys.argv[1]
                base64_image = generate_qr_code(qr_text)
                if base64_image:
                    print(f"Base64 QR code:\ndata:image/png;base64,{base64_image}")
                else:
                    print("Failed to generate QR code")
            else:
                print("Usage: python qr_generator.py 'text to encode'")
                print("Or pipe JSON: echo '{\"text\":\"hello\"}' | python qr_generator.py")
                
    except Exception as e:
        print(f"ERROR in main: {str(e)}", file=sys.stderr)
        print(json.dumps({'error': str(e)}))

if __name__ == "__main__":
    main()