using DaniGroup.Data;
using DaniGroup.Models;
using DaniGroup.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace DaniGroup.Controllers
{
    [Authorize]
    public class CheckoutController : Controller
    {
        private readonly ApplicationDbContext _context;
        private readonly UserManager<IdentityUser> _userManager;
        private readonly YocoPaymentService _yocoPaymentService;

        public CheckoutController(
            ApplicationDbContext context,
            UserManager<IdentityUser> userManager,
            YocoPaymentService yocoPaymentService)
        {
            _context = context;
            _userManager = userManager;
            _yocoPaymentService = yocoPaymentService;
        }

        [HttpGet]
        public async Task<IActionResult> Index()
        {
            var user = await _userManager.GetUserAsync(User);
            if (user == null) return Challenge();

            var cartItems = await _context.CartItems
                .Include(c => c.Product)
                .Where(c => c.UserId == user.Id)
                .ToListAsync();

            if (!cartItems.Any())
            {
                return RedirectToAction("Index", "Cart");
            }

            var model = new CheckoutViewModel
            {
                Email = user.Email ?? "",
                CartItems = cartItems,
                CartTotal = cartItems.Sum(c => (c.Product?.Price ?? 0) * c.Quantity)
            };

            return View(model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Index(CheckoutViewModel model)
        {
            var user = await _userManager.GetUserAsync(User);
            if (user == null) return Challenge();

            var cartItems = await _context.CartItems
                .Include(c => c.Product)
                .Where(c => c.UserId == user.Id)
                .ToListAsync();

            model.CartItems = cartItems;
            model.CartTotal = cartItems.Sum(c => (c.Product?.Price ?? 0) * c.Quantity);

            if (!cartItems.Any())
            {
                ModelState.AddModelError("", "Your cart is empty.");
            }

            if (!ModelState.IsValid)
            {
                return View(model);
            }

            var order = new Order
            {
                UserId = user.Id,
                FullName = model.FullName,
                Email = model.Email,
                AddressLine1 = model.AddressLine1,
                AddressLine2 = model.AddressLine2,
                City = model.City,
                State = model.State,
                PostalCode = model.PostalCode,
                Country = model.Country,
                TotalAmount = model.CartTotal,
                OrderStatus = "Pending Payment",
                PaymentStatus = "Pending",
                PaymentProvider = "Yoco"
            };

            foreach (var cartItem in cartItems)
            {
                if (cartItem.Product == null) continue;

                order.OrderItems.Add(new OrderItem
                {
                    ProductId = cartItem.ProductId,
                    Quantity = cartItem.Quantity,
                    UnitPrice = cartItem.Product.Price
                });
            }

            _context.Orders.Add(order);
            await _context.SaveChangesAsync();

            var yocoResult = await _yocoPaymentService.CreateCheckoutAsync(order);

            if (!string.IsNullOrWhiteSpace(yocoResult.ErrorMessage))
            {
                ModelState.AddModelError("", yocoResult.ErrorMessage);
                return View(model);
            }

            order.PaymentCheckoutId = yocoResult.Id;
            order.PaymentReference = yocoResult.Id;
            await _context.SaveChangesAsync();

            if (string.IsNullOrWhiteSpace(yocoResult.RedirectUrl))
            {
                throw new Exception("Yoco did not return a redirect URL. Check Yoco API response format.");
            }

            // In a more advanced production setup, only clear cart after verified payment success webhook.
            _context.CartItems.RemoveRange(cartItems);
            await _context.SaveChangesAsync();

            return Redirect(yocoResult.RedirectUrl);
        }
    }
}