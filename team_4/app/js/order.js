let small = 1
let medium = 2

const priceSmall = 400
const priceMedium = 443

function changeQty(type, value) {
  if (type === "small") {
    small = Math.max(0, small + value)
    document.getElementById("smallQty").innerText = small
    document.getElementById("mCount").innerText = small
  }

  if (type === "medium") {
    medium = Math.max(0, medium + value)
    document.getElementById("mediumQty").innerText = medium
    document.getElementById("lCount").innerText = medium
  }

  updateTotal()
}

function updateTotal() {
  const total = (small * priceSmall) + (medium * priceMedium)
  document.getElementById("total").innerText = total
}

function confirmOrder() {
  const order = {
    small,
    medium,
    total: (small * priceSmall) + (medium * priceMedium)
  }

  localStorage.setItem("order", JSON.stringify(order))
  window.location.href = "confirm.html"
}

updateTotal()
