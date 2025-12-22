let small = 1
let medium = 2
let large = 3
const priceSmall = 400
const priceMedium = 443
const priceLarge   = 500

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
   if (type === "large") {
    medium = Math.max(0, large + value)
    document.getElementById("mediumQty").innerText = large
    document.getElementById("lCount").innerText = large
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
    medium,large,

    total: (small * priceSmall) + (medium * priceMedium) + (large * priceLarge)
  }

  localStorage.setItem("order", JSON.stringify(order))
  window.location.href = "confirm.html"
}

updateTotal()
