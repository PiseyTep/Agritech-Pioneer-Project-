import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:login_farmer/models/booking_model.dart';
import 'package:login_farmer/pages/tractor.dart';
import 'package:login_farmer/service/api_service.dart';
import 'package:login_farmer/service/auth_service.dart';
import 'package:login_farmer/widgets/CustomButton.dart';

class RentalHistoryPage extends StatefulWidget {
  const RentalHistoryPage({Key? key}) : super(key: key);

  @override
  State<RentalHistoryPage> createState() => _RentalHistoryPageState();
}

class _RentalHistoryPageState extends State<RentalHistoryPage> {
  late ApiService _apiService;
  List<BookingModel> _rentals = [];
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    final authService = AuthService();
    _apiService = ApiService(authService: authService);
    _fetchRentals();
  }

  Future<void> _fetchRentals() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final result = await _apiService.getData('user/rentals');

      if (result['success'] == true) {
        final List<dynamic> rentalData = result['data']['data'] ?? [];
        setState(() {
          _rentals =
              rentalData.map((item) => BookingModel.fromJson(item)).toList();
          _isLoading = false;
        });
      } else {
        setState(() {
          _errorMessage = result['message'] ?? 'Failed to load rentals';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Error: $e';
        _isLoading = false;
      });
    }
  }

  Future<void> _cancelBooking(String id) async {
    try {
      final result = await _apiService.putData('user/rentals/$id/cancel', {});

      if (result['success'] == true) {
        _showSnackbar('Booking cancelled successfully', Colors.green);
        await _fetchRentals(); // Refresh the list
      } else {
        _showSnackbar(
            result['message'] ?? 'Failed to cancel booking', Colors.red);
      }
    } catch (e) {
      _showSnackbar('Error: $e', Colors.red);
    }
  }

  void _showSnackbar(String message, Color color) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: color,
        behavior: SnackBarBehavior.floating,
        margin: const EdgeInsets.all(16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'approved':
      case 'completed':
        return Colors.green;
      case 'pending':
      case 'in progress':
        return Colors.orange;
      case 'scheduled':
      case 'upcoming':
        return Colors.blue;
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  void _showBookingDetails(BookingModel rental) {
    final dateFormat = DateFormat('MMMM d, yyyy');

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Booking Details'),
        content: SingleChildScrollView(
          child: SizedBox(
            width: double.maxFinite,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Tractor image
                ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: rental.tractorImage.startsWith('http')
                      ? Image.network(
                          rental.tractorImage,
                          height: 150,
                          width: double.infinity,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) {
                            return Container(
                              height: 150,
                              color: Colors.grey[300],
                              child: const Center(
                                child: Icon(
                                  Icons.agriculture,
                                  size: 60,
                                  color: Colors.grey,
                                ),
                              ),
                            );
                          },
                        )
                      : Image.asset(
                          'assets/images/tractor_placeholder.png',
                          height: 150,
                          width: double.infinity,
                          fit: BoxFit.cover,
                        ),
                ),
                const SizedBox(height: 16),

                // Tractor and booking details
                Text(
                  rental.tractorName,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 8),

                // Status indicator
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: _getStatusColor(rental.status).withOpacity(0.2),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(
                    rental.status,
                    style: TextStyle(
                      color: _getStatusColor(rental.status),
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Customer details
                const Text(
                  'Customer Information',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 8),
                Text('Name: ${rental.customerName}'),
                Text('Phone: ${rental.customerPhone}'),
                Text('Address: ${rental.customerAddress}'),
                const SizedBox(height: 16),

                // Booking details
                const Text(
                  'Booking Details',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 8),
                Text(
                    'Date: ${dateFormat.format(DateTime.parse(rental.startDate))}'),
                Text('Land Size: ${rental.landSize} ${rental.landSizeUnit}'),
                Text('Total Price: \$${rental.totalPrice.toStringAsFixed(2)}'),
                const SizedBox(height: 8),

                // Admin approval status
                Row(
                  children: [
                    const Text('Admin approval: '),
                    Text(
                      rental.isAcceptedByAdmin ? 'Accepted' : 'Pending',
                      style: TextStyle(
                        color: rental.isAcceptedByAdmin
                            ? Colors.green
                            : Colors.orange,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),

                // Admin notes if available
                if (rental.adminNotes.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  const Text(
                    'Admin Notes:',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  const SizedBox(height: 4),
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.grey[100],
                      borderRadius: BorderRadius.circular(4),
                      border: Border.all(color: Colors.grey[300]!),
                    ),
                    child: Text(
                      rental.adminNotes,
                      style: TextStyle(
                        fontStyle: FontStyle.italic,
                        color: Colors.grey[700],
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
        actions: [
          if ((rental.status.toLowerCase() == 'pending' ||
                  rental.status.toLowerCase() == 'scheduled' ||
                  rental.status.toLowerCase() == 'upcoming') &&
              !rental.isAcceptedByAdmin)
            TextButton(
              onPressed: () {
                Navigator.of(context).pop();
                _confirmCancellation(rental);
              },
              child: const Text('Cancel Booking',
                  style: TextStyle(color: Colors.red)),
            ),
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Close'),
          ),
        ],
      ),
    );
  }

  void _confirmCancellation(BookingModel rental) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancel Booking'),
        content: const Text(
          'Are you sure you want to cancel this booking? This action cannot be undone.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('No'),
          ),
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              _cancelBooking(rental.id);
            },
            child:
                const Text('Yes, Cancel', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'My Rental History',
          style: TextStyle(color: Colors.white),
        ),
        backgroundColor: const Color(0xFF375534),
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _fetchRentals,
            color: Colors.white,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _errorMessage != null
              ? _buildErrorView()
              : _rentals.isEmpty
                  ? _buildEmptyView()
                  : RefreshIndicator(
                      onRefresh: _fetchRentals,
                      child: _buildRentalList(),
                    ),
    );
  }

  Widget _buildErrorView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(
              Icons.error_outline,
              size: 60,
              color: Colors.red,
            ),
            const SizedBox(height: 16),
            Text(
              _errorMessage!,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 16,
                color: Colors.red,
              ),
            ),
            const SizedBox(height: 24),
            CustomButton(
              onPressed: _fetchRentals,
              text: 'Try Again',
              backgroundColor: const Color(0xFF375534),
              icon: Icons.refresh,
              isFullWidth: false,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(
              Icons.history,
              size: 80,
              color: Colors.grey,
            ),
            const SizedBox(height: 16),
            const Text(
              'No rental history yet',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'You haven\'t rented any equipment yet. Book a tractor to get started!',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 16,
                color: Colors.grey,
              ),
            ),
            const SizedBox(height: 32),
            CustomButton(
              onPressed: () {
                Navigator.pushReplacement(
                  context,
                  MaterialPageRoute(
                      builder: (context) => TractorCategoriesPage()),
                );
              },
              text: 'Browse Tractors',
              icon: Icons.agriculture,
              backgroundColor: const Color(0xFF375534),
              isFullWidth: false,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRentalList() {
    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _rentals.length,
      itemBuilder: (context, index) {
        final rental = _rentals[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 16),
          elevation: 3,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          child: InkWell(
            onTap: () => _showBookingDetails(rental),
            borderRadius: BorderRadius.circular(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Status banner
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  decoration: BoxDecoration(
                    color: _getStatusColor(rental.status),
                    borderRadius: const BorderRadius.vertical(
                      top: Radius.circular(12),
                    ),
                  ),
                  child: Center(
                    child: Text(
                      rental.status.toUpperCase(),
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ),

                // Rental details
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Tractor image
                          ClipRRect(
                            borderRadius: BorderRadius.circular(8),
                            child: rental.tractorImage.startsWith('http')
                                ? Image.network(
                                    rental.tractorImage,
                                    width: 80,
                                    height: 80,
                                    fit: BoxFit.cover,
                                    errorBuilder: (context, error, stackTrace) {
                                      return Container(
                                        width: 80,
                                        height: 80,
                                        color: Colors.grey[300],
                                        child: const Icon(
                                          Icons.agriculture,
                                          size: 40,
                                          color: Colors.grey,
                                        ),
                                      );
                                    },
                                  )
                                : Image.asset(
                                    'assets/images/tractor_placeholder.png',
                                    width: 80,
                                    height: 80,
                                    fit: BoxFit.cover,
                                  ),
                          ),
                          const SizedBox(width: 16),

                          // Details
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  rental.tractorName,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 18,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'Date: ${DateFormat('MMM d, yyyy').format(DateTime.parse(rental.startDate))}',
                                  style: TextStyle(color: Colors.grey[700]),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'Land Size: ${rental.landSize} ${rental.landSizeUnit}',
                                  style: TextStyle(color: Colors.grey[700]),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'Total Price: \$${rental.totalPrice.toStringAsFixed(2)}',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),

                      // Cancellation button if eligible
                      if ((rental.status.toLowerCase() == 'pending' ||
                              rental.status.toLowerCase() == 'scheduled' ||
                              rental.status.toLowerCase() == 'upcoming') &&
                          !rental.isAcceptedByAdmin) ...[
                        const SizedBox(height: 12),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.end,
                          children: [
                            TextButton.icon(
                              onPressed: () => _confirmCancellation(rental),
                              icon: const Icon(Icons.cancel,
                                  color: Colors.red, size: 18),
                              label: const Text('Cancel Booking',
                                  style: TextStyle(color: Colors.red)),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
