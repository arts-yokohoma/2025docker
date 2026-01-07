import qrcode
import json
from datetime import datetime

def generate_pizza_qr(order_data, filename="pizza_receipt_qr.png"):
    """
    Generate QR code for pizza order receipt
    
    Parameters:
    order_data (dict): Dictionary containing order information
    filename (str): Name of the output PNG file
    """
    
    # Generate QR code
    qr = qrcode.QRCode(
        version=1,
        error_correction=qrcode.constants.ERROR_CORRECT_H,
        box_size=10,
        border=4,
    )
    
    # Add order data to QR code
    qr.add_data(json.dumps(order_data, indent=2))
    qr.make(fit=True)
    
    # Create QR code image
    img = qr.make_image(fill_color="#b1352f", back_color="white")
    
    # Save the image
    img.save(filename)
    print(f"QR code saved as {filename}")
    
    return filename

# Example usage
if __name__ == "__main__":
    # Sample order data
    sample_order = {
        "restaurant": "Pizza Palace",
        "orderId": "PZ-2023-00145",
        "orderDate": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        "customer": {
            "name": "John Doe",
            "phone": "+1 (555) 123-4567",
            "email": "john.doe@example.com",
            "address": "123 Main Street, City, State 12345"
        },
        "order": {
            "items": [
                {
                    "name": "Margherita Pizza (Small)",
                    "quantity": 1,
                    "price": 980
                },
                {
                    "name": "Pepperoni Pizza (Medium)",
                    "quantity": 2,
                    "price": 1280
                },
                {
                    "name": "Vegetarian Deluxe (Large)",
                    "quantity": 1,
                    "price": 1180
                }
            ],
            "subtotal": 5720,
            "tax": 458,
            "total": 6178,
            "currency": "JPY"
        },
        "delivery": {
            "estimatedTime": "30-45 minutes",
            "instructions": "Ring doorbell twice"
        },
        "storeInfo": {
            "name": "Pizza Palace - Downtown",
            "phone": "+1 (800) PIZZA-NOW",
            "address": "456 Pizza Street, City"
        },
        "notes": "Thank you for your order! Scan this QR code to verify receipt."
    }
    
    # Generate QR code
    generate_pizza_qr(sample_order)