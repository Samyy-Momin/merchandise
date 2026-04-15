import { getCart, addToCart, updateQty, removeItem, clearCart, total, type CartLine } from "@/lib/cart";

// Mock localStorage
const store: Record<string, string> = {};
beforeEach(() => {
  Object.keys(store).forEach((k) => delete store[k]);
  jest.spyOn(Storage.prototype, "getItem").mockImplementation((key) => store[key] ?? null);
  jest.spyOn(Storage.prototype, "setItem").mockImplementation((key, val) => {
    store[key] = val;
  });
  jest.spyOn(Storage.prototype, "removeItem").mockImplementation((key) => {
    delete store[key];
  });
});

const mockProduct = (id: number, price: number) => ({
  id,
  name: `Product ${id}`,
  price,
});

describe("Cart — Pure Functions (lib/cart.ts)", () => {
  test("getCart returns empty array initially", () => {
    expect(getCart()).toEqual([]);
  });

  test("addToCart adds a new product", () => {
    addToCart(mockProduct(1, 100), 2);
    const cart = getCart();
    expect(cart).toHaveLength(1);
    expect(cart[0].product.id).toBe(1);
    expect(cart[0].qty).toBe(2);
  });

  test("addToCart increments qty for existing product", () => {
    addToCart(mockProduct(1, 100), 1);
    addToCart(mockProduct(1, 100), 3);
    const cart = getCart();
    expect(cart).toHaveLength(1);
    expect(cart[0].qty).toBe(4);
  });

  test("addToCart handles multiple products", () => {
    addToCart(mockProduct(1, 100));
    addToCart(mockProduct(2, 200));
    expect(getCart()).toHaveLength(2);
  });

  test("updateQty changes quantity of a product", () => {
    addToCart(mockProduct(1, 100), 2);
    updateQty(1, 5);
    expect(getCart()[0].qty).toBe(5);
  });

  test("removeItem removes a product from cart", () => {
    addToCart(mockProduct(1, 100));
    addToCart(mockProduct(2, 200));
    removeItem(1);
    const cart = getCart();
    expect(cart).toHaveLength(1);
    expect(cart[0].product.id).toBe(2);
  });

  test("clearCart empties the cart", () => {
    addToCart(mockProduct(1, 100));
    addToCart(mockProduct(2, 200));
    clearCart();
    expect(getCart()).toEqual([]);
  });

  test("total calculates correct sum", () => {
    const cart: CartLine[] = [
      { product: mockProduct(1, 100) as any, qty: 2 },
      { product: mockProduct(2, 50) as any, qty: 3 },
    ];
    expect(total(cart)).toBe(350); // 100*2 + 50*3
  });

  test("total returns 0 for empty cart", () => {
    expect(total([])).toBe(0);
  });
});
