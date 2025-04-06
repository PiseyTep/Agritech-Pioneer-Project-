import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:login_farmer/Utility/ToastHelper.dart';
import 'package:login_farmer/models/booking_model.dart';
import 'package:login_farmer/service/api_service.dart';
import 'package:login_farmer/service/auth_service.dart';

import 'package:login_farmer/widgets/CustomButton.dart';

class BookingDetailsPage extends StatefulWidget {
  final TractorModel selectedTractor;

  const BookingDetailsPage({
    Key? key,
    required this.selectedTractor,
  }) : super(key: key);

  @override
  State<BookingDetailsPage> createState() => _BookingDetailsPageState();
}

class _BookingDetailsPageState extends State<BookingDetailsPage> {
  late ApiService _apiService;

  // Form controllers
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController();
  final _landSizeController = TextEditingController();

  // Booking details
  DateTime _startDate = DateTime.now().add(const Duration(days: 1));
  String _landSizeUnit = 'Acres';

  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    final authService = AuthService();
    _apiService = ApiService(authService: authService);

    // Pre-fill user information if available
    _loadUserInfo();
  }

  Future<void> _loadUserInfo() async {
    try {
      final result = await _apiService.getProfile();
      if (result['success'] == true) {
        final userData = result['data'];
        setState(() {
          _nameController.text = userData['name'] ?? '';
          _phoneController.text = userData['phone'] ?? '';
          _addressController.text = userData['address'] ?? '';
        });
      }
    } catch (e) {
      // Silent failure - we'll just have empty fields
      print('Error loading user profile: $e');
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _landSizeController.dispose();
    super.dispose();
  }

  Future<void> _selectDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _startDate,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 90)),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFF375534),
              onPrimary: Colors.white,
              onSurface: Colors.black,
            ),
          ),
          child: child!,
        );
      },
    );

    if (picked != null && picked != _startDate) {
      setState(() {
        _startDate = picked;
      });
    }
  }

  void _submitBooking() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() => _isLoading = true);

    try {
      // Calculate total price based on land size
      final landSize = double.parse(_landSizeController.text);
      final totalPrice = landSize * widget.selectedTractor.pricePerAcre;

      // Format date for API
      final formattedDate = DateFormat('yyyy-MM-dd').format(_startDate);

      // Prepare booking data
      final bookingData = {
        'tractor_id': widget.selectedTractor.id,
        'rental_date': formattedDate,
        'end_date': formattedDate, // Same day for now
        'land_size': landSize,
        'land_size_unit': _landSizeUnit,
        'farmer_name': _nameController.text,
        'phone': _phoneController.text,
        'address': _addressController.text,
        'total_price': totalPrice,
      };

      // Submit to API
      final result = await _apiService.postData('user/rentals', bookingData);

      setState(() => _isLoading = false);

      if (result['success'] == true) {
        // Show success message
        ToastHelper.showSuccess(context, 'Booking submitted successfully!');

        // Go back to previous screen after short delay
        Future.delayed(const Duration(seconds: 1), () {
          Navigator.of(context).pop();
        });
      } else {
        // Show error message
        ToastHelper.showError(
            context, result['message'] ?? 'Failed to submit booking');
      }
    } catch (e) {
      setState(() => _isLoading = false);
      ToastHelper.showError(context, 'Error: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Booking Details',
          style: TextStyle(color: Colors.white),
        ),
        backgroundColor: const Color(0xFF375534),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Tractor Information Card
            _buildTractorInfoCard(),

            const SizedBox(height: 24),

            // Booking Form
            _buildBookingForm(),

            const SizedBox(height: 24),

            // Submit Button
            _isLoading
                ? const Center(child: CircularProgressIndicator())
                : CustomButton(
                    onPressed: _submitBooking,
                    text: 'Submit Booking Request',
                    backgroundColor: const Color(0xFF375534),
                  ),
          ],
        ),
      ),
    );
  }

  Widget _buildTractorInfoCard() {
    return Card(
      elevation: 3,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Tractor image
          ClipRRect(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
            child: widget.selectedTractor.imageUrl.startsWith('http')
                ? Image.network(
                    widget.selectedTractor.imageUrl,
                    height: 200,
                    width: double.infinity,
                    fit: BoxFit.cover,
                    errorBuilder: (context, error, stackTrace) {
                      return Container(
                        height: 200,
                        color: Colors.grey[300],
                        child: const Center(
                          child: Icon(
                            Icons.agriculture,
                            size: 80,
                            color: Colors.grey,
                          ),
                        ),
                      );
                    },
                  )
                : Image.asset(
                    widget.selectedTractor.imageUrl,
                    height: 200,
                    width: double.infinity,
                    fit: BoxFit.cover,
                    errorBuilder: (context, error, stackTrace) {
                      return Container(
                        height: 200,
                        color: Colors.grey[300],
                        child: const Center(
                          child: Icon(
                            Icons.agriculture,
                            size: 80,
                            color: Colors.grey,
                          ),
                        ),
                      );
                    },
                  ),
          ),

          // Tractor details
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  widget.selectedTractor.name,
                  style: const TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    const Icon(Icons.category, color: Colors.grey, size: 20),
                    const SizedBox(width: 8),
                    Text('Type: ${widget.selectedTractor.type}'),
                  ],
                ),
                if (widget.selectedTractor.brand.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.business, color: Colors.grey, size: 20),
                      const SizedBox(width: 8),
                      Text('Brand: ${widget.selectedTractor.brand}'),
                    ],
                  ),
                ],
                if (widget.selectedTractor.horsePower > 0) ...[
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.speed, color: Colors.grey, size: 20),
                      const SizedBox(width: 8),
                      Text(
                          'Horse Power: ${widget.selectedTractor.horsePower} HP'),
                    ],
                  ),
                ],
                const SizedBox(height: 4),
                Row(
                  children: [
                    const Icon(Icons.attach_money,
                        color: Colors.grey, size: 20),
                    const SizedBox(width: 8),
                    Text(
                        'Price per Acre: \$${widget.selectedTractor.pricePerAcre.toStringAsFixed(2)}'),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  widget.selectedTractor.description,
                  style: TextStyle(
                    color: Colors.grey[700],
                    height: 1.5,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBookingForm() {
    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Booking Information',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 16),

          // Date selection
          InkWell(
            onTap: () => _selectDate(context),
            child: InputDecorator(
              decoration: InputDecoration(
                labelText: 'Select Date',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
                prefixIcon: const Icon(Icons.calendar_today),
              ),
              child: Text(
                DateFormat('EEEE, MMMM d, yyyy').format(_startDate),
                style: const TextStyle(fontSize: 16),
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Land size
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                flex: 2,
                child: TextFormField(
                  controller: _landSizeController,
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(
                    labelText: 'Land Size',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter land size';
                    }
                    if (double.tryParse(value) == null) {
                      return 'Please enter a valid number';
                    }
                    if (double.parse(value) <= 0) {
                      return 'Land size must be greater than 0';
                    }
                    return null;
                  },
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: DropdownButtonFormField<String>(
                  value: _landSizeUnit,
                  decoration: InputDecoration(
                    labelText: 'Unit',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'Acres', child: Text('Acres')),
                    DropdownMenuItem(
                        value: 'Hectares', child: Text('Hectares')),
                  ],
                  onChanged: (value) {
                    if (value != null) {
                      setState(() {
                        _landSizeUnit = value;
                      });
                    }
                  },
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Contact information
          TextFormField(
            controller: _nameController,
            decoration: InputDecoration(
              labelText: 'Your Name',
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(8),
              ),
              prefixIcon: const Icon(Icons.person),
            ),
            validator: (value) {
              if (value == null || value.isEmpty) {
                return 'Please enter your name';
              }
              return null;
            },
          ),
          const SizedBox(height: 16),

          TextFormField(
            controller: _phoneController,
            keyboardType: TextInputType.phone,
            decoration: InputDecoration(
              labelText: 'Phone Number',
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(8),
              ),
              prefixIcon: const Icon(Icons.phone),
            ),
            validator: (value) {
              if (value == null || value.isEmpty) {
                return 'Please enter phone number';
              }
              return null;
            },
          ),
          const SizedBox(height: 16),

          TextFormField(
            controller: _addressController,
            decoration: InputDecoration(
              labelText: 'Farm Address',
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(8),
              ),
              prefixIcon: const Icon(Icons.location_on),
            ),
            validator: (value) {
              if (value == null || value.isEmpty) {
                return 'Please enter farm address';
              }
              return null;
            },
          ),
        ],
      ),
    );
  }
}
