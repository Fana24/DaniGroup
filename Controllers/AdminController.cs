using DaniGroup.Data;
using DaniGroup.Helpers;
using DaniGroup.Models;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.Rendering;
using Microsoft.EntityFrameworkCore;

namespace DaniGroup.Controllers
{
    [Authorize(Roles = "Admin")]
    public class AdminController : Controller
    {
        private readonly ApplicationDbContext _context;
        private readonly IWebHostEnvironment _environment;

        public AdminController(ApplicationDbContext context, IWebHostEnvironment environment)
        {
            _context = context;
            _environment = environment;
        }

        public IActionResult Index()
        {
            return View();
        }

        public async Task<IActionResult> Products()
        {
            var products = await _context.Products
                .Include(p => p.Category)
                .OrderByDescending(p => p.Id)
                .ToListAsync();

            return View(products);
        }

        [HttpGet]
        public async Task<IActionResult> AddProduct()
        {
            await LoadCategories();
            return View(new Product());
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> AddProduct(Product model, IFormFile? imageFile)
        {
            await LoadCategories(model.CategoryId);

            if (!ModelState.IsValid)
            {
                TempData["ErrorMessage"] = "Please fix the form errors and try again.";
                return View(model);
            }

            if (imageFile != null)
            {
                if (!FileValidationHelper.IsValidImage(imageFile))
                {
                    ModelState.AddModelError("", "Only JPG, JPEG, PNG, or WEBP images up to 2 MB are allowed.");
                    TempData["ErrorMessage"] = "Image upload failed validation.";
                    return View(model);
                }

                string uploadsFolder = Path.Combine(_environment.WebRootPath, "uploads", "products");
                Directory.CreateDirectory(uploadsFolder);

                string fileName = Guid.NewGuid().ToString() + Path.GetExtension(imageFile.FileName);
                string filePath = Path.Combine(uploadsFolder, fileName);

                using (var stream = new FileStream(filePath, FileMode.Create))
                {
                    await imageFile.CopyToAsync(stream);
                }

                model.ImagePath = "/uploads/products/" + fileName;
            }

            try
            {
                _context.Products.Add(model);
                await _context.SaveChangesAsync();

                TempData["SuccessMessage"] = "Product added successfully.";
                return RedirectToAction("Products");
            }
            catch (Exception ex)
            {
                ModelState.AddModelError("", "An error occurred while saving the product: " + ex.Message);
                TempData["ErrorMessage"] = "Could not save the product.";
                return View(model);
            }
        }

        [HttpGet]
        public async Task<IActionResult> EditProduct(int id)
        {
            var product = await _context.Products.FindAsync(id);

            if (product == null)
                return NotFound();

            await LoadCategories(product.CategoryId);
            return View(product);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> EditProduct(Product model, IFormFile? imageFile)
        {
            await LoadCategories(model.CategoryId);

            if (!ModelState.IsValid)
            {
                TempData["ErrorMessage"] = "Please fix the form errors and try again.";
                return View(model);
            }

            var product = await _context.Products.FindAsync(model.Id);

            if (product == null)
                return NotFound();

            product.Name = model.Name;
            product.Description = model.Description;
            product.Price = model.Price;
            product.StockQuantity = model.StockQuantity;
            product.CategoryId = model.CategoryId;
            product.IsFeatured = model.IsFeatured;

            if (imageFile != null)
            {
                if (!FileValidationHelper.IsValidImage(imageFile))
                {
                    ModelState.AddModelError("", "Only JPG, JPEG, PNG, or WEBP images up to 2 MB are allowed.");
                    TempData["ErrorMessage"] = "Image upload failed validation.";
                    return View(model);
                }

                string uploadsFolder = Path.Combine(_environment.WebRootPath, "uploads", "products");
                Directory.CreateDirectory(uploadsFolder);

                string fileName = Guid.NewGuid().ToString() + Path.GetExtension(imageFile.FileName);
                string filePath = Path.Combine(uploadsFolder, fileName);

                using (var stream = new FileStream(filePath, FileMode.Create))
                {
                    await imageFile.CopyToAsync(stream);
                }

                product.ImagePath = "/uploads/products/" + fileName;
            }

            try
            {
                await _context.SaveChangesAsync();

                TempData["SuccessMessage"] = "Product updated successfully.";
                return RedirectToAction("Products");
            }
            catch (Exception ex)
            {
                ModelState.AddModelError("", "An error occurred while updating the product: " + ex.Message);
                TempData["ErrorMessage"] = "Could not update the product.";
                return View(model);
            }
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> DeleteProduct(int id)
        {
            var product = await _context.Products
                .Include(p => p.Reviews)
                .FirstOrDefaultAsync(p => p.Id == id);

            if (product == null)
            {
                TempData["ErrorMessage"] = "Product not found.";
                return RedirectToAction("Products");
            }

            try
            {
                var cartItems = await _context.CartItems
                    .Where(c => c.ProductId == id)
                    .ToListAsync();

                if (cartItems.Any())
                {
                    _context.CartItems.RemoveRange(cartItems);
                }

                var reviews = await _context.ProductReviews
                    .Where(r => r.ProductId == id)
                    .ToListAsync();

                if (reviews.Any())
                {
                    _context.ProductReviews.RemoveRange(reviews);
                }

                var orderItemsExist = await _context.OrderItems.AnyAsync(oi => oi.ProductId == id);
                if (orderItemsExist)
                {
                    TempData["ErrorMessage"] = "This product cannot be deleted because it exists in customer orders.";
                    return RedirectToAction("Products");
                }

                _context.Products.Remove(product);
                await _context.SaveChangesAsync();

                TempData["SuccessMessage"] = "Product deleted successfully.";
            }
            catch (Exception ex)
            {
                TempData["ErrorMessage"] = "Error deleting product: " + ex.Message;
            }

            return RedirectToAction("Products");
        }

        private async Task LoadCategories(int? selectedCategoryId = null)
        {
            var categories = await _context.Categories.ToListAsync();
            ViewBag.Categories = new SelectList(categories, "Id", "Name", selectedCategoryId);
        }

        public async Task<IActionResult> Orders()
        {
            var orders = await _context.Orders
                .OrderByDescending(o => o.CreatedAt)
                .ToListAsync();

            return View(orders);
        }

        public async Task<IActionResult> OrderDetails(int id)
        {
            var order = await _context.Orders
                .Include(o => o.OrderItems)
                .ThenInclude(oi => oi.Product)
                .FirstOrDefaultAsync(o => o.Id == id);

            if (order == null)
                return NotFound();

            return View(order);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> UpdateOrderStatus(int orderId, string orderStatus)
        {
            var order = await _context.Orders.FindAsync(orderId);

            if (order == null)
                return NotFound();

            order.OrderStatus = orderStatus;
            await _context.SaveChangesAsync();

            TempData["SuccessMessage"] = "Order status updated successfully.";
            return RedirectToAction("OrderDetails", new { id = orderId });
        }

        public async Task<IActionResult> Returns()
        {
            var returnsList = await _context.ReturnRequests
                .OrderByDescending(r => r.CreatedAt)
                .ToListAsync();

            return View(returnsList);
        }

        public async Task<IActionResult> Messages()
        {
            var messages = await _context.ContactMessages
                .OrderByDescending(m => m.CreatedAt)
                .ToListAsync();

            return View(messages);
        }
    }
}