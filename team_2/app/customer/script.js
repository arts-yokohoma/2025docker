function toggleOrderSearch() {
    const orderSearch = document.getElementById("order-search");
    if (orderSearch.style.display === "none" || orderSearch.style.display === "") {
        orderSearch.style.display = "block";
    } else {
        orderSearch.style.display = "none";
    }
}