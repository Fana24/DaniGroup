using DaniGroup.Models;
using Microsoft.AspNetCore.Identity;
using Microsoft.EntityFrameworkCore;

namespace DaniGroup.Data
{
    public static class DbInitializer
    {
        public static async Task SeedAsync(IServiceProvider serviceProvider)
        {
            var context = serviceProvider.GetRequiredService<ApplicationDbContext>();
            var roleManager = serviceProvider.GetRequiredService<RoleManager<IdentityRole>>();
            var userManager = serviceProvider.GetRequiredService<UserManager<IdentityUser>>();

            await context.Database.MigrateAsync();

            if (!await roleManager.RoleExistsAsync("Admin"))
            {
                await roleManager.CreateAsync(new IdentityRole("Admin"));
            }

            string adminEmail = "admin@danigroup.com";
            string adminPassword = "Admin@12345";

            var adminUser = await userManager.FindByEmailAsync(adminEmail);

            if (adminUser == null)
            {
                adminUser = new IdentityUser
                {
                    UserName = adminEmail,
                    Email = adminEmail,
                    EmailConfirmed = true
                };

                var result = await userManager.CreateAsync(adminUser, adminPassword);

                if (!result.Succeeded)
                {
                    throw new Exception("Admin user creation failed: " +
                        string.Join(", ", result.Errors.Select(e => e.Description)));
                }

                await userManager.AddToRoleAsync(adminUser, "Admin");
            }

            if (!context.Categories.Any())
            {
                context.Categories.AddRange(
                    new Category { Name = "Car Parts" },
                    new Category { Name = "Accessories" },
                    new Category { Name = "Bike Parts" }
                );

                await context.SaveChangesAsync();
            }

            if (!context.Products.Any())
            {
                var carParts = await context.Categories.FirstAsync(c => c.Name == "Car Parts");
                var accessories = await context.Categories.FirstAsync(c => c.Name == "Accessories");
                var bikeParts = await context.Categories.FirstAsync(c => c.Name == "Bike Parts");

                context.Products.AddRange(
                    new Product
                    {
                        Name = "Brake Pads Set",
                        Description = "High quality brake pads for reliable stopping performance.",
                        Price = 89.99m,
                        StockQuantity = 20,
                        CategoryId = carParts.Id,
                        IsFeatured = true,
                        ImagePath = "/images/products/brake-pads.jpg"
                    },
                    new Product
                    {
                        Name = "Steering Wheel Cover",
                        Description = "Premium black and orange steering wheel cover.",
                        Price = 24.99m,
                        StockQuantity = 50,
                        CategoryId = accessories.Id,
                        IsFeatured = true,
                        ImagePath = "/images/products/steering-cover.jpg"
                    },
                    new Product
                    {
                        Name = "Bike Chain Kit",
                        Description = "Durable replacement chain kit for bike maintenance.",
                        Price = 39.99m,
                        StockQuantity = 35,
                        CategoryId = bikeParts.Id,
                        IsFeatured = true,
                        ImagePath = "/images/products/bike-chain.jpg"
                    }
                );

                await context.SaveChangesAsync();
            }
        }
    }
}